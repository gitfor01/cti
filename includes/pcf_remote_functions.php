<?php
/**
 * PCF Remote Integration Functions
 * 
 * This file contains functions to connect to remote PCF databases and APIs
 * Supports multiple connection types and provides fallback mechanisms
 */

require_once __DIR__ . '/../config/pcf_remote_config.php';

/**
 * Get PCF database connection based on configuration
 * @return PDO|null PCF database connection or null on failure
 */
function getPcfRemoteConnection() {
    $connectionType = PCF_CONNECTION_TYPE;
    
    switch ($connectionType) {
        case 'local_sqlite':
            return getPcfSqliteConnection();
        case 'remote_sqlite':
            return getPcfRemoteSqliteConnection();
        case 'remote_mysql':
            return getPcfMysqlConnection();
        case 'remote_postgresql':
            return getPcfPostgresqlConnection();
        case 'remote_api':
            return null; // API connections don't use PDO
        default:
            error_log("Unknown PCF connection type: " . $connectionType);
            return null;
    }
}

/**
 * Get local SQLite PCF connection
 * @return PDO|null
 */
function getPcfSqliteConnection() {
    try {
        $pcfDbPath = PCF_SQLITE_PATH;
        
        if (!file_exists($pcfDbPath)) {
            error_log("PCF SQLite database file not found: " . $pcfDbPath);
            return null;
        }
        
        $pcfPdo = new PDO("sqlite:" . $pcfDbPath);
        $pcfPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pcfPdo->setAttribute(PDO::ATTR_TIMEOUT, PCF_QUERY_TIMEOUT);
        
        return $pcfPdo;
    } catch (PDOException $e) {
        error_log("PCF SQLite connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Get remote SQLite PCF connection
 * Downloads remote SQLite file and creates local cached connection
 * @return PDO|null
 */
function getPcfRemoteSqliteConnection() {
    try {
        $cacheFile = PCF_REMOTE_SQLITE_LOCAL_CACHE;
        $cacheAge = file_exists($cacheFile) ? (time() - filemtime($cacheFile)) : PHP_INT_MAX;
        
        // Check if cache is still valid
        if ($cacheAge > PCF_REMOTE_SQLITE_CACHE_DURATION) {
            // Download fresh copy from remote server
            if (!downloadRemoteSqliteFile()) {
                error_log("Failed to download remote SQLite file");
                return null;
            }
        }
        
        if (!file_exists($cacheFile)) {
            error_log("Remote SQLite cache file not found: " . $cacheFile);
            return null;
        }
        
        $pcfPdo = new PDO("sqlite:" . $cacheFile);
        $pcfPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pcfPdo->setAttribute(PDO::ATTR_TIMEOUT, PCF_QUERY_TIMEOUT);
        
        return $pcfPdo;
    } catch (PDOException $e) {
        error_log("PCF Remote SQLite connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Download remote SQLite file to local cache
 * @return bool Success status
 */
function downloadRemoteSqliteFile() {
    $method = PCF_REMOTE_SQLITE_METHOD;
    $host = PCF_REMOTE_SQLITE_HOST;
    $remotePath = PCF_REMOTE_SQLITE_PATH;
    $localPath = PCF_REMOTE_SQLITE_LOCAL_CACHE;
    
    switch ($method) {
        case 'ssh':
            return downloadViaSsh($host, $remotePath, $localPath);
        case 'http':
        case 'https':
            return downloadViaHttp($host, $remotePath, $localPath);
        case 'ftp':
            return downloadViaFtp($host, $remotePath, $localPath);
        case 'smb':
            return downloadViaSmb($host, $remotePath, $localPath);
        default:
            error_log("Unsupported remote SQLite method: " . $method);
            return false;
    }
}

/**
 * Download SQLite file via SSH/SCP
 * @param string $host Remote host
 * @param string $remotePath Remote file path
 * @param string $localPath Local cache path
 * @return bool Success status
 */
function downloadViaSsh($host, $remotePath, $localPath) {
    $username = PCF_REMOTE_SQLITE_USERNAME;
    $password = PCF_REMOTE_SQLITE_PASSWORD;
    $port = PCF_REMOTE_SQLITE_PORT;
    
    // Create temporary directory if needed
    $localDir = dirname($localPath);
    if (!is_dir($localDir)) {
        mkdir($localDir, 0755, true);
    }
    
    // Use SCP command to download file
    $scpCommand = sprintf(
        'scp -P %d -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null %s@%s:%s %s 2>/dev/null',
        $port,
        escapeshellarg($username),
        escapeshellarg($host),
        escapeshellarg($remotePath),
        escapeshellarg($localPath)
    );
    
    // For password authentication, you might need to use sshpass or key-based auth
    if ($password) {
        $scpCommand = sprintf('sshpass -p %s %s', escapeshellarg($password), $scpCommand);
    }
    
    exec($scpCommand, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($localPath)) {
        return true;
    } else {
        error_log("SSH download failed for remote SQLite file. Command: " . $scpCommand);
        return false;
    }
}

/**
 * Download SQLite file via HTTP/HTTPS
 * @param string $host Remote host
 * @param string $remotePath Remote file path
 * @param string $localPath Local cache path
 * @return bool Success status
 */
function downloadViaHttp($host, $remotePath, $localPath) {
    $url = (PCF_REMOTE_SQLITE_METHOD === 'https' ? 'https://' : 'http://') . $host . $remotePath;
    
    // Create temporary directory if needed
    $localDir = dirname($localPath);
    if (!is_dir($localDir)) {
        mkdir($localDir, 0755, true);
    }
    
    $context = stream_context_create([
        'http' => [
            'timeout' => PCF_CONNECTION_TIMEOUT,
            'user_agent' => 'CTI-PCF-Remote/1.0'
        ]
    ]);
    
    // Add authentication if provided
    if (PCF_REMOTE_SQLITE_USERNAME && PCF_REMOTE_SQLITE_PASSWORD) {
        $auth = base64_encode(PCF_REMOTE_SQLITE_USERNAME . ':' . PCF_REMOTE_SQLITE_PASSWORD);
        $context = stream_context_create([
            'http' => [
                'timeout' => PCF_CONNECTION_TIMEOUT,
                'user_agent' => 'CTI-PCF-Remote/1.0',
                'header' => "Authorization: Basic " . $auth
            ]
        ]);
    }
    
    $data = file_get_contents($url, false, $context);
    
    if ($data !== false) {
        return file_put_contents($localPath, $data) !== false;
    } else {
        error_log("HTTP download failed for remote SQLite file: " . $url);
        return false;
    }
}

/**
 * Download SQLite file via FTP
 * @param string $host Remote host
 * @param string $remotePath Remote file path
 * @param string $localPath Local cache path
 * @return bool Success status
 */
function downloadViaFtp($host, $remotePath, $localPath) {
    $username = PCF_REMOTE_SQLITE_USERNAME;
    $password = PCF_REMOTE_SQLITE_PASSWORD;
    $port = PCF_REMOTE_SQLITE_PORT ?: 21;
    
    // Create temporary directory if needed
    $localDir = dirname($localPath);
    if (!is_dir($localDir)) {
        mkdir($localDir, 0755, true);
    }
    
    $ftpConnection = ftp_connect($host, $port, PCF_CONNECTION_TIMEOUT);
    
    if (!$ftpConnection) {
        error_log("FTP connection failed to: " . $host);
        return false;
    }
    
    if (!ftp_login($ftpConnection, $username, $password)) {
        error_log("FTP login failed for user: " . $username);
        ftp_close($ftpConnection);
        return false;
    }
    
    ftp_pasv($ftpConnection, true);
    
    $success = ftp_get($ftpConnection, $localPath, $remotePath, FTP_BINARY);
    ftp_close($ftpConnection);
    
    if (!$success) {
        error_log("FTP download failed for remote SQLite file: " . $remotePath);
        return false;
    }
    
    return true;
}

/**
 * Download SQLite file via SMB (requires smbclient)
 * @param string $host Remote host
 * @param string $remotePath Remote file path
 * @param string $localPath Local cache path
 * @return bool Success status
 */
function downloadViaSmb($host, $remotePath, $localPath) {
    $username = PCF_REMOTE_SQLITE_USERNAME;
    $password = PCF_REMOTE_SQLITE_PASSWORD;
    
    // Create temporary directory if needed
    $localDir = dirname($localPath);
    if (!is_dir($localDir)) {
        mkdir($localDir, 0755, true);
    }
    
    // Use smbclient to download file
    $smbCommand = sprintf(
        'smbclient //%s/%s -U %s%%%s -c "get %s %s" 2>/dev/null',
        escapeshellarg($host),
        'share', // You might need to configure the share name
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($remotePath),
        escapeshellarg($localPath)
    );
    
    exec($smbCommand, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($localPath)) {
        return true;
    } else {
        error_log("SMB download failed for remote SQLite file. Command: " . $smbCommand);
        return false;
    }
}

/**
 * Get remote MySQL PCF connection
 * @return PDO|null
 */
function getPcfMysqlConnection() {
    try {
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
            PCF_MYSQL_HOST,
            PCF_MYSQL_PORT,
            PCF_MYSQL_DATABASE
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => PCF_CONNECTION_TIMEOUT,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        if (PCF_USE_SSL) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = PCF_VERIFY_SSL;
            if (PCF_SSL_CERT_PATH) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = PCF_SSL_CERT_PATH;
            }
        }
        
        $pcfPdo = new PDO($dsn, PCF_MYSQL_USERNAME, PCF_MYSQL_PASSWORD, $options);
        
        return $pcfPdo;
    } catch (PDOException $e) {
        error_log("PCF MySQL connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Get remote PostgreSQL PCF connection
 * @return PDO|null
 */
function getPcfPostgresqlConnection() {
    try {
        $dsn = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s",
            PCF_POSTGRESQL_HOST,
            PCF_POSTGRESQL_PORT,
            PCF_POSTGRESQL_DATABASE
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => PCF_CONNECTION_TIMEOUT
        ];
        
        if (PCF_USE_SSL) {
            $dsn .= ";sslmode=" . (PCF_VERIFY_SSL ? "require" : "prefer");
        }
        
        $pcfPdo = new PDO($dsn, PCF_POSTGRESQL_USERNAME, PCF_POSTGRESQL_PASSWORD, $options);
        
        return $pcfPdo;
    } catch (PDOException $e) {
        error_log("PCF PostgreSQL connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Get PCF data via REST API
 * @param string $endpoint API endpoint (e.g., 'issues', 'projects')
 * @param array $params Query parameters
 * @return array|null API response data or null on failure
 */
function getPcfApiData($endpoint, $params = []) {
    try {
        $url = rtrim(PCF_API_BASE_URL, '/') . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Add authentication header
        if (PCF_API_TOKEN) {
            $headers[] = 'Authorization: Bearer ' . PCF_API_TOKEN;
        } elseif (PCF_API_USERNAME && PCF_API_PASSWORD) {
            $headers[] = 'Authorization: Basic ' . base64_encode(PCF_API_USERNAME . ':' . PCF_API_PASSWORD);
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => PCF_CONNECTION_TIMEOUT,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => PCF_VERIFY_SSL,
                'verify_peer_name' => PCF_VERIFY_SSL
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            error_log("PCF API request failed for endpoint: " . $endpoint);
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("PCF API response JSON decode error: " . json_last_error_msg());
            return null;
        }
        
        return $data;
    } catch (Exception $e) {
        error_log("PCF API error: " . $e->getMessage());
        return null;
    }
}

/**
 * Sync findings from remote PCF to CTI database
 * @param PDO $ctiPdo CTI database connection
 * @return array Result array with success status and message
 */
function syncRemotePcfFindings($ctiPdo) {
    try {
        $connectionType = PCF_CONNECTION_TYPE;
        
        if ($connectionType === 'remote_api') {
            return syncPcfFindingsFromApi($ctiPdo);
        } else {
            return syncPcfFindingsFromDatabase($ctiPdo);
        }
    } catch (Exception $e) {
        error_log("PCF remote sync error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sync findings from PCF database (MySQL/PostgreSQL/SQLite)
 * @param PDO $ctiPdo CTI database connection
 * @return array Result array with success status and message
 */
function syncPcfFindingsFromDatabase($ctiPdo) {
    try {
        $pcfPdo = getPcfRemoteConnection();
        if (!$pcfPdo) {
            return ['success' => false, 'error' => 'Could not connect to remote PCF database'];
        }
        
        // Create PCF findings table if it doesn't exist
        createPcfFindingsTable($ctiPdo);
        
        // Get table and field mappings
        $tableMapping = $GLOBALS['PCF_TABLE_MAPPING'];
        $fieldMapping = $GLOBALS['PCF_FIELD_MAPPING'];
        
        $issuesTable = $tableMapping['issues'];
        $projectsTable = $tableMapping['projects'];
        
        // Build query with field mapping
        $query = "
            SELECT 
                i.{$fieldMapping['issue_id']} as id,
                i.{$fieldMapping['issue_name']} as name,
                i.{$fieldMapping['issue_description']} as description,
                i.{$fieldMapping['issue_cvss']} as cvss,
                i.{$fieldMapping['issue_cwe']} as cwe,
                i.{$fieldMapping['issue_cve']} as cve,
                i.{$fieldMapping['issue_status']} as status,
                i.{$fieldMapping['issue_type']} as type,
                i.{$fieldMapping['issue_fix']} as fix,
                i.{$fieldMapping['issue_technical']} as technical,
                i.{$fieldMapping['issue_risks']} as risks,
                i.{$fieldMapping['issue_references']} as references,
                i.{$fieldMapping['issue_url_path']} as url_path,
                i.{$fieldMapping['issue_param']} as param,
                i.{$fieldMapping['project_id']} as project_id,
                p.{$fieldMapping['project_name']} as project_name,
                p.{$fieldMapping['project_description']} as project_description,
                p.{$fieldMapping['project_start_date']} as start_date,
                p.{$fieldMapping['project_end_date']} as end_date
            FROM {$issuesTable} i
            LEFT JOIN {$projectsTable} p ON i.{$fieldMapping['project_id']} = p.id
            ORDER BY i.{$fieldMapping['issue_cvss']} DESC
        ";
        
        $stmt = $pcfPdo->prepare($query);
        $stmt->execute();
        $pcfIssues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return processPcfFindings($ctiPdo, $pcfIssues);
        
    } catch (Exception $e) {
        error_log("PCF database sync error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sync findings from PCF API
 * @param PDO $ctiPdo CTI database connection
 * @return array Result array with success status and message
 */
function syncPcfFindingsFromApi($ctiPdo) {
    try {
        // Create PCF findings table if it doesn't exist
        createPcfFindingsTable($ctiPdo);
        
        // Get issues from API
        $issues = getPcfApiData('issues');
        if (!$issues) {
            return ['success' => false, 'error' => 'Could not fetch issues from PCF API'];
        }
        
        // Get projects from API
        $projects = getPcfApiData('projects');
        $projectsById = [];
        if ($projects) {
            foreach ($projects as $project) {
                $projectsById[$project['id']] = $project;
            }
        }
        
        // Combine issues with project data
        $pcfIssues = [];
        foreach ($issues as $issue) {
            $projectId = $issue['project_id'] ?? null;
            $project = $projectsById[$projectId] ?? null;
            
            $combinedIssue = array_merge($issue, [
                'project_name' => $project['name'] ?? null,
                'project_description' => $project['description'] ?? null,
                'start_date' => $project['start_date'] ?? null,
                'end_date' => $project['end_date'] ?? null
            ]);
            
            $pcfIssues[] = $combinedIssue;
        }
        
        return processPcfFindings($ctiPdo, $pcfIssues);
        
    } catch (Exception $e) {
        error_log("PCF API sync error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process and insert PCF findings into CTI database
 * @param PDO $ctiPdo CTI database connection
 * @param array $pcfIssues Array of PCF issues
 * @return array Result array with success status and message
 */
function processPcfFindings($ctiPdo, $pcfIssues) {
    try {
        // Clear existing PCF findings
        $ctiPdo->exec("DELETE FROM pcf_findings");
        
        $insertCount = 0;
        $batchSize = PCF_SYNC_BATCH_SIZE;
        $batches = array_chunk($pcfIssues, $batchSize);
        
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
        
        foreach ($batches as $batch) {
            $ctiPdo->beginTransaction();
            
            try {
                foreach ($batch as $issue) {
                    // Use project end date as finding creation date, or current time if no end date
                    $createdAt = date('Y-m-d H:i:s');
                    if (!empty($issue['end_date'])) {
                        $createdAt = date('Y-m-d H:i:s', is_numeric($issue['end_date']) ? $issue['end_date'] : strtotime($issue['end_date']));
                    } elseif (!empty($issue['start_date'])) {
                        $createdAt = date('Y-m-d H:i:s', is_numeric($issue['start_date']) ? $issue['start_date'] : strtotime($issue['start_date']));
                    }
                    
                    $insertStmt->execute([
                        ':pcf_id' => $issue['id'] ?? '',
                        ':name' => $issue['name'] ?? '',
                        ':description' => $issue['description'] ?? '',
                        ':url_path' => $issue['url_path'] ?? '',
                        ':cvss' => $issue['cvss'] ?? 0.0,
                        ':cwe' => $issue['cwe'] ?? null,
                        ':cve' => $issue['cve'] ?? '',
                        ':status' => $issue['status'] ?? 'open',
                        ':project_id' => $issue['project_id'] ?? '',
                        ':project_name' => $issue['project_name'] ?? '',
                        ':project_description' => $issue['project_description'] ?? '',
                        ':type' => $issue['type'] ?? '',
                        ':fix_description' => $issue['fix'] ?? '',
                        ':param' => $issue['param'] ?? '',
                        ':technical' => $issue['technical'] ?? '',
                        ':risks' => $issue['risks'] ?? '',
                        ':references' => $issue['references'] ?? '',
                        ':start_date' => !empty($issue['start_date']) ? date('Y-m-d H:i:s', is_numeric($issue['start_date']) ? $issue['start_date'] : strtotime($issue['start_date'])) : null,
                        ':end_date' => !empty($issue['end_date']) ? date('Y-m-d H:i:s', is_numeric($issue['end_date']) ? $issue['end_date'] : strtotime($issue['end_date'])) : null,
                        ':created_at' => $createdAt
                    ]);
                    
                    $insertCount++;
                }
                
                $ctiPdo->commit();
            } catch (Exception $e) {
                $ctiPdo->rollback();
                throw $e;
            }
        }
        
        // Log sync result
        logPcfSync($ctiPdo, $insertCount, 'success', "Synced {$insertCount} findings from remote PCF");
        
        return [
            'success' => true,
            'count' => $insertCount,
            'message' => "Successfully synced {$insertCount} findings from remote PCF"
        ];
        
    } catch (Exception $e) {
        logPcfSync($ctiPdo, 0, 'error', $e->getMessage());
        throw $e;
    }
}

/**
 * Test remote PCF connection
 * @param array|null $testConfig Optional test configuration to override global constants
 * @return array Test results
 */
function testRemotePcfConnection($testConfig = null) {
    // Use test config if provided, otherwise use global constants
    $connectionType = $testConfig ? $testConfig['connection_type'] : PCF_CONNECTION_TYPE;
    
    $results = [
        'connection_type' => $connectionType,
        'connection_success' => false,
        'data_available' => false,
        'issue_count' => 0,
        'project_count' => 0,
        'error' => null
    ];
    
    try {
        if ($connectionType === 'remote_api') {
            // Test API connection
            $issues = getPcfApiDataWithConfig('issues', ['limit' => 1], $testConfig);
            $projects = getPcfApiDataWithConfig('projects', ['limit' => 1], $testConfig);
            
            if ($issues !== null) {
                $results['connection_success'] = true;
                $results['data_available'] = !empty($issues);
                
                // Get full counts
                $allIssues = getPcfApiDataWithConfig('issues', [], $testConfig);
                $allProjects = getPcfApiDataWithConfig('projects', [], $testConfig);
                
                $results['issue_count'] = is_array($allIssues) ? count($allIssues) : 0;
                $results['project_count'] = is_array($allProjects) ? count($allProjects) : 0;
            }
        } else {
            // Test database connection
            $pcfPdo = getPcfRemoteConnectionWithConfig($testConfig);
            
            if ($pcfPdo) {
                $results['connection_success'] = true;
                
                // Test data availability
                $tableMapping = $GLOBALS['PCF_TABLE_MAPPING'];
                $issuesTable = $tableMapping['issues'];
                $projectsTable = $tableMapping['projects'];
                
                try {
                    $stmt = $pcfPdo->query("SELECT COUNT(*) as count FROM {$issuesTable}");
                    $results['issue_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    $results['data_available'] = $results['issue_count'] > 0;
                } catch (Exception $e) {
                    $results['error'] = "Could not query issues table: " . $e->getMessage();
                }
                
                try {
                    $stmt = $pcfPdo->query("SELECT COUNT(*) as count FROM {$projectsTable}");
                    $results['project_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                } catch (Exception $e) {
                    // Projects table might not exist in some PCF versions
                    $results['project_count'] = 0;
                }
            }
        }
        
    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
    }
    
    return $results;
}

/**
 * Log PCF sync operation
 * @param PDO $ctiPdo CTI database connection
 * @param int $count Number of findings synced
 * @param string $status 'success' or 'error'
 * @param string $message Log message
 */
function logPcfSync($ctiPdo, $count, $status, $message) {
    try {
        $stmt = $ctiPdo->prepare("
            INSERT INTO pcf_sync_log (sync_time, findings_count, status, message)
            VALUES (NOW(), :count, :status, :message)
        ");
        $stmt->execute([
            ':count' => $count,
            ':status' => $status,
            ':message' => $message
        ]);
    } catch (Exception $e) {
        error_log("Failed to log PCF sync: " . $e->getMessage());
    }
}

/**
 * Create cache directory if it doesn't exist
 */
function ensurePcfCacheDirectory() {
    if (PCF_CACHE_ENABLED && !is_dir(PCF_CACHE_DIR)) {
        mkdir(PCF_CACHE_DIR, 0755, true);
    }
}

/**
 * Get cached data
 * @param string $key Cache key
 * @return mixed|null Cached data or null if not found/expired
 */
function getPcfCache($key) {
    if (!PCF_CACHE_ENABLED) {
        return null;
    }
    
    ensurePcfCacheDirectory();
    $cacheFile = PCF_CACHE_DIR . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheData = file_get_contents($cacheFile);
    $data = unserialize($cacheData);
    
    if ($data['expires'] < time()) {
        unlink($cacheFile);
        return null;
    }
    
    return $data['content'];
}

/**
 * Set cached data
 * @param string $key Cache key
 * @param mixed $data Data to cache
 */
function setPcfCache($key, $data) {
    if (!PCF_CACHE_ENABLED) {
        return;
    }
    
    ensurePcfCacheDirectory();
    $cacheFile = PCF_CACHE_DIR . md5($key) . '.cache';
    
    $cacheData = [
        'content' => $data,
        'expires' => time() + PCF_CACHE_DURATION
    ];
    
    file_put_contents($cacheFile, serialize($cacheData));
}

/**
 * Get PCF database connection using test configuration
 * @param array|null $testConfig Test configuration
 * @return PDO|null PCF database connection or null on failure
 */
function getPcfRemoteConnectionWithConfig($testConfig = null) {
    if (!$testConfig) {
        return getPcfRemoteConnection();
    }
    
    $connectionType = $testConfig['connection_type'];
    
    switch ($connectionType) {
        case 'local_sqlite':
            return getPcfSqliteConnectionWithConfig($testConfig);
        case 'remote_sqlite':
            return getPcfRemoteSqliteConnectionWithConfig($testConfig);
        case 'remote_mysql':
            return getPcfMysqlConnectionWithConfig($testConfig);
        case 'remote_postgresql':
            return getPcfPostgresqlConnectionWithConfig($testConfig);
        case 'remote_api':
            return null; // API connections don't use PDO
        default:
            error_log("Unknown PCF connection type: " . $connectionType);
            return null;
    }
}

/**
 * Get local SQLite PCF connection with test config
 * @param array $testConfig Test configuration
 * @return PDO|null
 */
function getPcfSqliteConnectionWithConfig($testConfig) {
    try {
        $pcfDbPath = $testConfig['sqlite_path'];
        
        if (!file_exists($pcfDbPath)) {
            throw new Exception("PCF SQLite database file not found: " . $pcfDbPath);
        }
        
        $pcfPdo = new PDO("sqlite:" . $pcfDbPath);
        $pcfPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pcfPdo;
    } catch (Exception $e) {
        error_log("PCF SQLite connection failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get remote SQLite PCF connection with test config
 * @param array $testConfig Test configuration
 * @return PDO|null
 */
function getPcfRemoteSqliteConnectionWithConfig($testConfig) {
    try {
        // For testing, we'll try to download/access the remote file
        // This is a simplified version - in production you'd want full implementation
        $cacheFile = $testConfig['remote_sqlite_cache'] ?: '/tmp/test_pcf_remote.sqlite3';
        
        // Try to download the file (simplified for testing)
        if ($testConfig['remote_sqlite_method'] === 'http' || $testConfig['remote_sqlite_method'] === 'https') {
            $url = $testConfig['remote_sqlite_method'] . '://' . $testConfig['remote_sqlite_host'] . $testConfig['remote_sqlite_path'];
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'ignore_errors' => true
                ]
            ]);
            
            $data = file_get_contents($url, false, $context);
            if ($data === false) {
                throw new Exception("Failed to download remote SQLite file from: " . $url);
            }
            
            file_put_contents($cacheFile, $data);
        } else {
            throw new Exception("Remote SQLite method '" . $testConfig['remote_sqlite_method'] . "' not implemented in test mode");
        }
        
        if (!file_exists($cacheFile)) {
            throw new Exception("Remote SQLite cache file not found: " . $cacheFile);
        }
        
        $pcfPdo = new PDO("sqlite:" . $cacheFile);
        $pcfPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pcfPdo;
    } catch (Exception $e) {
        error_log("PCF Remote SQLite connection failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get remote MySQL PCF connection with test config
 * @param array $testConfig Test configuration
 * @return PDO|null
 */
function getPcfMysqlConnectionWithConfig($testConfig) {
    try {
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
            $testConfig['mysql_host'],
            $testConfig['mysql_port'],
            $testConfig['mysql_database']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        $pcfPdo = new PDO(
            $dsn,
            $testConfig['mysql_username'],
            $testConfig['mysql_password'],
            $options
        );
        
        return $pcfPdo;
    } catch (PDOException $e) {
        error_log("PCF MySQL connection failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get remote PostgreSQL PCF connection with test config
 * @param array $testConfig Test configuration
 * @return PDO|null
 */
function getPcfPostgresqlConnectionWithConfig($testConfig) {
    try {
        $dsn = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s",
            $testConfig['postgresql_host'],
            $testConfig['postgresql_port'],
            $testConfig['postgresql_database']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pcfPdo = new PDO(
            $dsn,
            $testConfig['postgresql_username'],
            $testConfig['postgresql_password'],
            $options
        );
        
        return $pcfPdo;
    } catch (PDOException $e) {
        error_log("PCF PostgreSQL connection failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get PCF data via REST API with test config
 * @param string $endpoint API endpoint
 * @param array $params Query parameters
 * @param array|null $testConfig Test configuration
 * @return array|null API response data or null on failure
 */
function getPcfApiDataWithConfig($endpoint, $params = [], $testConfig = null) {
    if (!$testConfig) {
        return getPcfApiData($endpoint, $params);
    }
    
    try {
        $url = rtrim($testConfig['api_base_url'], '/') . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Add authentication header
        if ($testConfig['api_token']) {
            $headers[] = 'Authorization: Bearer ' . $testConfig['api_token'];
        } elseif ($testConfig['api_username'] && $testConfig['api_password']) {
            $headers[] = 'Authorization: Basic ' . base64_encode($testConfig['api_username'] . ':' . $testConfig['api_password']);
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("API request failed for endpoint: " . $endpoint);
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("API response JSON decode error: " . json_last_error_msg());
        }
        
        return $data;
    } catch (Exception $e) {
        error_log("PCF API error: " . $e->getMessage());
        throw $e;
    }
}
?>