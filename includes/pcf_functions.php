<?php
/**
 * PCF Integration Functions
 * 
 * This file contains functions to connect to the PCF database and sync findings
 */

/**
 * Get PCF database connection
 * @return PDO|null PCF database connection or null on failure
 */
function getPcfConnection() {
    try {
        // PCF uses SQLite database
        $pcfDbPath = '/Users/ammarfahad/Downloads/Others/CTI Proj/pcf/configuration/database.sqlite3';
        
        if (!file_exists($pcfDbPath)) {
            error_log("PCF database file not found: " . $pcfDbPath);
            return null;
        }
        
        $pcfPdo = new PDO("sqlite:" . $pcfDbPath);
        $pcfPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pcfPdo;
    } catch (PDOException $e) {
        error_log("PCF database connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Sync findings from PCF database to CTI database
 * @param PDO $ctiPdo CTI database connection
 * @return array Result array with success status and message
 */
function syncPcfFindings($ctiPdo) {
    try {
        $pcfPdo = getPcfConnection();
        if (!$pcfPdo) {
            return ['success' => false, 'error' => 'Could not connect to PCF database'];
        }
        
        // Create PCF findings table if it doesn't exist
        createPcfFindingsTable($ctiPdo);
        
        // Get all issues from PCF with project information
        $stmt = $pcfPdo->prepare("
            SELECT 
                i.id,
                i.name,
                i.description,
                i.url_path,
                i.cvss,
                i.cwe,
                i.cve,
                i.status,
                i.project_id,
                i.type,
                i.fix,
                i.param,
                i.technical,
                i.risks,
                i.\"references\",
                p.name as project_name,
                p.description as project_description,
                p.start_date,
                p.end_date
            FROM Issues i
            LEFT JOIN Projects p ON i.project_id = p.id
            ORDER BY i.cvss DESC
        ");
        $stmt->execute();
        $pcfIssues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clear existing PCF findings
        $ctiPdo->exec("DELETE FROM pcf_findings");
        
        $insertCount = 0;
        $insertStmt = $ctiPdo->prepare("
            INSERT INTO pcf_findings (
                pcf_id, name, description, url_path, cvss, cwe, cve, status,
                project_id, project_name, project_description, type, fix_description,
                param, technical, risks, `references`, start_date, end_date, created_at
            ) VALUES (
                :pcf_id, :name, :description, :url_path, :cvss, :cwe, :cve, :status,
                :project_id, :project_name, :project_description, :type, :fix_description,
                :param, :technical, :risks, :references, :start_date, :end_date, :created_at
            )
        ");
        
        foreach ($pcfIssues as $issue) {
            // Use project end date as finding creation date, or current time if no end date
            $createdAt = date('Y-m-d H:i:s');
            if ($issue['end_date']) {
                // Set creation date to project end date (when findings are typically finalized)
                $createdAt = date('Y-m-d H:i:s', $issue['end_date']);
            } elseif ($issue['start_date']) {
                // Fallback to start date if no end date
                $createdAt = date('Y-m-d H:i:s', $issue['start_date']);
            }
            
            $insertStmt->execute([
                ':pcf_id' => $issue['id'],
                ':name' => $issue['name'] ?: 'Unnamed Finding',
                ':description' => $issue['description'] ?: '',
                ':url_path' => $issue['url_path'] ?: '',
                ':cvss' => floatval($issue['cvss']),
                ':cwe' => intval($issue['cwe']),
                ':cve' => $issue['cve'] ?: '',
                ':status' => $issue['status'] ?: 'unknown',
                ':project_id' => $issue['project_id'],
                ':project_name' => $issue['project_name'] ?: 'Unknown Project',
                ':project_description' => $issue['project_description'] ?: '',
                ':type' => $issue['type'] ?: 'custom',
                ':fix_description' => $issue['fix'] ?: '',
                ':param' => $issue['param'] ?: '',
                ':technical' => $issue['technical'] ?: '',
                ':risks' => $issue['risks'] ?: '',
                ':references' => $issue['references'] ?: '',
                ':start_date' => $issue['start_date'] ? date('Y-m-d H:i:s', $issue['start_date']) : null,
                ':end_date' => $issue['end_date'] ? date('Y-m-d H:i:s', $issue['end_date']) : null,
                ':created_at' => $createdAt
            ]);
            $insertCount++;
        }
        
        // Update last sync time
        updateLastSyncTime($ctiPdo);
        
        return ['success' => true, 'count' => $insertCount];
        
    } catch (Exception $e) {
        error_log("PCF sync error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Create PCF findings table in CTI database
 * @param PDO $pdo CTI database connection
 */
function createPcfFindingsTable($pdo) {
    $sql = "
        CREATE TABLE IF NOT EXISTS pcf_findings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pcf_id VARCHAR(255) NOT NULL,
            name VARCHAR(500) NOT NULL,
            description TEXT,
            url_path TEXT,
            cvss DECIMAL(3,1) DEFAULT 0.0,
            cwe INT DEFAULT 0,
            cve VARCHAR(50) DEFAULT '',
            status VARCHAR(50) DEFAULT '',
            project_id VARCHAR(255),
            project_name VARCHAR(255),
            project_description TEXT,
            type VARCHAR(50) DEFAULT 'custom',
            fix_description TEXT,
            param TEXT,
            technical TEXT,
            risks TEXT,
            `references` TEXT,
            start_date DATETIME NULL,
            end_date DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cvss (cvss),
            INDEX idx_status (status),
            INDEX idx_project_id (project_id),
            INDEX idx_pcf_id (pcf_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    
    // Create sync log table
    $syncLogSql = "
        CREATE TABLE IF NOT EXISTS pcf_sync_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sync_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            findings_count INT DEFAULT 0,
            status ENUM('success', 'error') DEFAULT 'success',
            message TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($syncLogSql);
}

/**
 * Get PCF findings with pagination and filters
 * @param PDO $pdo CTI database connection
 * @param int $limit Number of results per page
 * @param int $offset Offset for pagination
 * @param string $projectFilter Project filter
 * @param string $severityFilter Severity filter
 * @param string $statusFilter Status filter
 * @param string $monthFilter Month filter (YYYY-MM format)
 * @param string $sortBy Column to sort by
 * @param string $sortOrder Sort order (asc or desc)
 * @return array Array of PCF findings
 */
function getPcfFindings($pdo, $limit = 50, $offset = 0, $projectFilter = '', $severityFilter = '', $statusFilter = '', $monthFilter = '', $sortBy = 'cvss', $sortOrder = 'desc') {
    $sql = "SELECT * FROM pcf_findings WHERE 1=1";
    $params = [];
    
    if (!empty($projectFilter)) {
        $sql .= " AND project_id = :project_id";
        $params[':project_id'] = $projectFilter;
    }
    
    if (!empty($severityFilter)) {
        switch ($severityFilter) {
            case 'critical':
                $sql .= " AND cvss >= 9.0";
                break;
            case 'high':
                $sql .= " AND cvss >= 7.0 AND cvss < 9.0";
                break;
            case 'medium':
                $sql .= " AND cvss >= 4.0 AND cvss < 7.0";
                break;
            case 'low':
                $sql .= " AND cvss > 0.0 AND cvss < 4.0";
                break;
            case 'info':
                $sql .= " AND cvss = 0.0";
                break;
        }
    }
    
    if (!empty($statusFilter)) {
        $sql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }
    
    if (!empty($monthFilter)) {
        $sql .= " AND DATE_FORMAT(created_at, '%Y-%m') = :month";
        $params[':month'] = $monthFilter;
    }
    
    // Validate and sanitize sort parameters
    $allowedSortColumns = ['name', 'project_name', 'cvss', 'status', 'created_at'];
    $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'cvss';
    $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
    
    // Special handling for severity sorting (sort by CVSS score)
    if ($sortBy === 'cvss') {
        $sql .= " ORDER BY cvss " . $sortOrder . ", created_at DESC";
    } else {
        $sql .= " ORDER BY " . $sortBy . " " . $sortOrder . ", cvss DESC";
    }
    
    $sql .= " LIMIT " . (int)$offset . ", " . (int)$limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get total count of PCF findings with filters
 * @param PDO $pdo CTI database connection
 * @param string $projectFilter Project filter
 * @param string $severityFilter Severity filter
 * @param string $statusFilter Status filter
 * @param string $monthFilter Month filter (YYYY-MM format)
 * @return int Total count
 */
function getPcfFindingsCount($pdo, $projectFilter = '', $severityFilter = '', $statusFilter = '', $monthFilter = '') {
    $sql = "SELECT COUNT(*) FROM pcf_findings WHERE 1=1";
    $params = [];
    
    if (!empty($projectFilter)) {
        $sql .= " AND project_id = :project_id";
        $params[':project_id'] = $projectFilter;
    }
    
    if (!empty($severityFilter)) {
        switch ($severityFilter) {
            case 'critical':
                $sql .= " AND cvss >= 9.0";
                break;
            case 'high':
                $sql .= " AND cvss >= 7.0 AND cvss < 9.0";
                break;
            case 'medium':
                $sql .= " AND cvss >= 4.0 AND cvss < 7.0";
                break;
            case 'low':
                $sql .= " AND cvss > 0.0 AND cvss < 4.0";
                break;
            case 'info':
                $sql .= " AND cvss = 0.0";
                break;
        }
    }
    
    if (!empty($statusFilter)) {
        $sql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }
    
    if (!empty($monthFilter)) {
        $sql .= " AND DATE_FORMAT(created_at, '%Y-%m') = :month";
        $params[':month'] = $monthFilter;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchColumn();
}

/**
 * Get unique projects from PCF findings
 * @param PDO $pdo CTI database connection
 * @return array Array of projects
 */
function getPcfProjects($pdo) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT project_id as id, project_name as name 
        FROM pcf_findings 
        WHERE project_id IS NOT NULL AND project_name IS NOT NULL
        ORDER BY project_name
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get count of findings by severity level
 * @param PDO $pdo CTI database connection
 * @param string $severity Severity level (critical, high, medium, low, info)
 * @return int Count of findings
 */
function getPcfFindingsCountBySeverity($pdo, $severity) {
    $sql = "SELECT COUNT(*) FROM pcf_findings WHERE ";
    
    switch ($severity) {
        case 'critical':
            $sql .= "cvss >= 9.0";
            break;
        case 'high':
            $sql .= "cvss >= 7.0 AND cvss < 9.0";
            break;
        case 'medium':
            $sql .= "cvss >= 4.0 AND cvss < 7.0";
            break;
        case 'low':
            $sql .= "cvss > 0.0 AND cvss < 4.0";
            break;
        case 'info':
            $sql .= "cvss = 0.0";
            break;
        default:
            return 0;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchColumn();
}

/**
 * Get severity class for CSS styling
 * @param float $cvss CVSS score
 * @return string CSS class name
 */
function getSeverityClass($cvss) {
    if ($cvss >= 9.0) return 'severity-critical';
    if ($cvss >= 7.0) return 'severity-high';
    if ($cvss >= 4.0) return 'severity-medium';
    if ($cvss > 0.0) return 'severity-low';
    return 'severity-info';
}

/**
 * Get severity text
 * @param float $cvss CVSS score
 * @return string Severity text
 */
function getSeverityText($cvss) {
    if ($cvss >= 9.0) return 'Critical';
    if ($cvss >= 7.0) return 'High';
    if ($cvss >= 4.0) return 'Medium';
    if ($cvss > 0.0) return 'Low';
    return 'Info';
}

/**
 * Get status color for badges
 * @param string $status Status string
 * @return string Bootstrap color class
 */
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'open':
        case 'new':
            return 'danger';
        case 'fixed':
        case 'closed':
            return 'success';
        case 'retest':
        case 'testing':
            return 'warning';
        case 'false positive':
        case 'duplicate':
            return 'secondary';
        default:
            return 'dark';
    }
}

/**
 * Update last sync time
 * @param PDO $pdo CTI database connection
 */
function updateLastSyncTime($pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO pcf_sync_log (sync_time, findings_count, status) 
        VALUES (NOW(), (SELECT COUNT(*) FROM pcf_findings), 'success')
    ");
    $stmt->execute();
}

/**
 * Get last sync time
 * @param PDO $pdo CTI database connection
 * @return string|null Last sync time or null
 */
function getLastSyncTime($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT sync_time FROM pcf_sync_log ORDER BY sync_time DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['sync_time'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Auto-sync PCF findings if last sync was more than 1 hour ago
 * @param PDO $pdo CTI database connection
 * @return bool True if sync was performed
 */
function autoSyncPcfFindings($pdo) {
    $lastSync = getLastSyncTime($pdo);
    
    if (!$lastSync || (time() - strtotime($lastSync)) > 3600) { // 1 hour = 3600 seconds
        $result = syncPcfFindings($pdo);
        return $result['success'];
    }
    
    return false;
}

/**
 * Get high/critical findings that are old and not sent to risk
 * @param PDO $pdo CTI database connection
 * @return array Array of warning findings
 */
function getWarningFindings($pdo) {
    try {
        // Get findings that are:
        // 1. High or Critical severity (CVSS >= 7.0)
        // 2. Created more than 1 month ago (now based on project end date)
        // 3. Status is not 'Fixed', 'Closed', or contains 'Sent To Risk'
        $stmt = $pdo->prepare("
            SELECT 
                id, name, project_name, cvss, status, created_at, end_date,
                DATEDIFF(NOW(), created_at) as days_old,
                'Finding from completed project' as age_reason
            FROM pcf_findings 
            WHERE cvss >= 7.0 
            AND created_at <= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            AND status NOT IN ('Fixed', 'Closed')
            AND status NOT LIKE '%Sent To Risk%'
            AND status NOT LIKE '%sent to risk%'
            ORDER BY cvss DESC, created_at ASC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting warning findings: " . $e->getMessage());
        return [];
    }
}

/**
 * Get count of warning findings
 * @param PDO $pdo CTI database connection
 * @return int Count of warning findings
 */
function getWarningFindingsCount($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM pcf_findings 
            WHERE cvss >= 7.0 
            AND created_at <= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            AND status NOT IN ('Fixed', 'Closed')
            AND status NOT LIKE '%Sent To Risk%'
            AND status NOT LIKE '%sent to risk%'
        ");
        $stmt->execute();
        
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting warning findings count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Update finding status in both CTI and PCF databases
 * @param PDO $ctiPdo CTI database connection
 * @param int $findingId CTI finding ID
 * @param string $newStatus New status ('Sent To Risk' or 'Closed')
 * @return array Result array with success status and message
 */
function updateFindingStatus($ctiPdo, $findingId, $newStatus) {
    try {
        // Validate status
        $allowedStatuses = ['Sent To Risk', 'Closed'];
        if (!in_array($newStatus, $allowedStatuses)) {
            return ['success' => false, 'error' => 'Invalid status. Allowed: ' . implode(', ', $allowedStatuses)];
        }
        
        // Get the finding details from CTI database
        $stmt = $ctiPdo->prepare("SELECT pcf_id, name FROM pcf_findings WHERE id = ?");
        $stmt->execute([$findingId]);
        $finding = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$finding) {
            return ['success' => false, 'error' => 'Finding not found in CTI database'];
        }
        
        $pcfId = $finding['pcf_id'];
        $findingName = $finding['name'];
        
        // Update status in CTI database
        $stmt = $ctiPdo->prepare("UPDATE pcf_findings SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $findingId]);
        
        // Update status in PCF database
        $pcfPdo = getPcfConnection();
        if ($pcfPdo) {
            $stmt = $pcfPdo->prepare("UPDATE Issues SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $pcfId]);
        } else {
            // If PCF connection fails, still consider it successful since CTI is updated
            error_log("Warning: Could not connect to PCF to update finding status");
        }
        
        return [
            'success' => true, 
            'message' => "Finding '{$findingName}' marked as '{$newStatus}'"
        ];
        
    } catch (Exception $e) {
        error_log("Error updating finding status: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update finding status to 'Sent To Risk' in both CTI and PCF databases
 * @param PDO $ctiPdo CTI database connection
 * @param int $findingId CTI finding ID
 * @return array Result array with success status and message
 */
function markFindingAsSentToRisk($ctiPdo, $findingId) {
    return updateFindingStatus($ctiPdo, $findingId, 'Sent To Risk');
}

/**
 * Update finding status to 'Closed' in both CTI and PCF databases
 * @param PDO $ctiPdo CTI database connection
 * @param int $findingId CTI finding ID
 * @return array Result array with success status and message
 */
function markFindingAsClosed($ctiPdo, $findingId) {
    return updateFindingStatus($ctiPdo, $findingId, 'Closed');
}

/**
 * Bulk update multiple findings status
 * @param PDO $ctiPdo CTI database connection
 * @param array $findingIds Array of CTI finding IDs
 * @param string $newStatus New status ('Sent To Risk' or 'Closed')
 * @return array Result array with success status and details
 */
function updateMultipleFindingsStatus($ctiPdo, $findingIds, $newStatus) {
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($findingIds as $findingId) {
        $result = updateFindingStatus($ctiPdo, $findingId, $newStatus);
        $results[] = $result;
        
        if ($result['success']) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    return [
        'success' => $errorCount === 0,
        'total' => count($findingIds),
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'details' => $results
    ];
}

/**
 * Bulk update multiple findings status to 'Sent To Risk'
 * @param PDO $ctiPdo CTI database connection
 * @param array $findingIds Array of CTI finding IDs
 * @return array Result array with success status and details
 */
function markMultipleFindingsAsSentToRisk($ctiPdo, $findingIds) {
    return updateMultipleFindingsStatus($ctiPdo, $findingIds, 'Sent To Risk');
}

/**
 * Bulk update multiple findings status to 'Closed'
 * @param PDO $ctiPdo CTI database connection
 * @param array $findingIds Array of CTI finding IDs
 * @return array Result array with success status and details
 */
function markMultipleFindingsAsClosed($ctiPdo, $findingIds) {
    return updateMultipleFindingsStatus($ctiPdo, $findingIds, 'Closed');
}
?>