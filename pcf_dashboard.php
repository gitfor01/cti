<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/pcf_functions.php';
// Require authentication for accessing the dashboard
require_once 'auth.php';

// Handle sync request
if (isset($_POST['sync_pcf'])) {
    $syncResult = syncPcfFindings($pdo);
    $syncMessage = $syncResult['success'] ? 
        "Successfully synced {$syncResult['count']} findings from PCF." : 
        "Error syncing PCF data: {$syncResult['error']}";
}

// Get PCF findings with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get filter parameters
$projectFilter = isset($_GET['project']) ? $_GET['project'] : '';
$severityFilter = isset($_GET['severity']) ? $_GET['severity'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$monthFilter = isset($_GET['month']) ? $_GET['month'] : '';

// Get sorting parameters
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'cvss';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'desc';

$pcfFindings = getPcfFindings($pdo, $limit, $offset, $projectFilter, $severityFilter, $statusFilter, $monthFilter, $sortBy, $sortOrder);
$totalFindings = getPcfFindingsCount($pdo, $projectFilter, $severityFilter, $statusFilter, $monthFilter);
$totalPages = ceil($totalFindings / $limit);

// Get unique projects for filter dropdown
$projects = getPcfProjects($pdo);

// Get last sync time
$lastSync = getLastSyncTime($pdo);

// Get warning findings
$warningFindings = getWarningFindings($pdo);
$warningCount = count($warningFindings);

$pageTitle = 'PT Dashboard - AMT';
include 'includes/header.php';
?>

<style>
.severity-critical { background-color: #dc3545; color: white; }
.severity-high { background-color: #fd7e14; color: white; }
.severity-medium { background-color: #ffc107; color: black; }
.severity-low { background-color: #28a745; color: white; }
.severity-info { background-color: #17a2b8; color: white; }
.sync-info { font-size: 0.9em; color: #6c757d; }
.filter-section { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }

/* Modern Stat Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 20px;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Severity-specific colors */
.stat-card.critical .stat-icon {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}
.stat-card.critical .stat-number {
    color: #dc3545;
}

.stat-card.high .stat-icon {
    background: linear-gradient(135deg, #fd7e14, #e8690b);
    color: white;
}
.stat-card.high .stat-number {
    color: #fd7e14;
}

.stat-card.medium .stat-icon {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
}
.stat-card.medium .stat-number {
    color: #ffc107;
}

.stat-card.low .stat-icon {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
}
.stat-card.low .stat-number {
    color: #28a745;
}

/* Sortable table headers */
.sortable-header {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 20px !important;
    transition: background-color 0.2s ease;
}

.sortable-header:hover {
    background-color: rgba(0,0,0,0.05);
}

.sort-icon {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    opacity: 0.5;
    transition: opacity 0.2s ease;
}

.sortable-header:hover .sort-icon {
    opacity: 0.8;
}

.sortable-header.active {
    background-color: rgba(0,123,255,0.1);
    font-weight: 600;
}

.sortable-header.active .sort-icon {
    opacity: 1;
    color: #007bff;
    font-weight: bold;
}

.sort-icon.asc::before {
    content: "▲";
}

.sort-icon.desc::before {
    content: "▼";
}

.sort-icon.none::before {
    content: "⇅";
}

/* Smooth scrolling for better UX */
html {
    scroll-behavior: smooth;
}

/* Highlight the findings table when scrolled to */
#findings-table {
    scroll-margin-top: 20px;
}
</style>

<?php if (isset($syncMessage)): ?>
    <div class="alert alert-<?php echo $syncResult['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $syncResult['success'] ? 'check-circle' : 'exclamation-triangle'; ?>"></i> 
        <?php echo htmlspecialchars($syncMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-shield-alt"></i> PT Dashboard</h1>
        <p class="text-muted">Security findings from Penetration Testing activities</p>
    </div>
</div>

<!-- Sync Information -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="sync-info">
            <i class="fas fa-clock"></i> 
            Last sync: <?php echo $lastSync ? date('M j, Y H:i:s', strtotime($lastSync)) : 'Never'; ?>
            | Total findings: <?php echo number_format($totalFindings); ?>
        </div>
    </div>
</div>

<!-- Warning Section for Old High/Critical Findings -->
<?php if ($warningCount > 0): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-clock"></i> Attention Required: Old High/Critical Findings
                    </h5>
                    <p class="mb-2">
                        <strong><?php echo $warningCount; ?></strong> high or critical severity finding<?php echo $warningCount > 1 ? 's' : ''; ?> 
                        from completed projects or older than 1 month <?php echo $warningCount > 1 ? 'have' : 'has'; ?> not been sent to risk management.
                    </p>
                    <button class="btn btn-sm btn-outline-warning" type="button" data-bs-toggle="collapse" data-bs-target="#warningDetails" aria-expanded="false" aria-controls="warningDetails">
                        <i class="fas fa-list"></i> View Details (<?php echo $warningCount; ?> finding<?php echo $warningCount > 1 ? 's' : ''; ?>)
                    </button>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        
        <!-- Collapsible Warning Details -->
        <div class="collapse" id="warningDetails">
            <div class="card border-warning">
                <div class="card-header bg-warning bg-opacity-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-list"></i> Findings Requiring Attention</h6>
                        <div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-success" id="markSelectedAsRisk" disabled>
                                    <i class="fas fa-share"></i> Mark Selected as Sent To Risk
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" id="markSelectedAsClosed" disabled>
                                    <i class="fas fa-times-circle"></i> Mark Selected as Closed
                                </button>
                            </div>
                            <div class="btn-group ms-2" role="group">
                                <button type="button" class="btn btn-sm btn-outline-success" id="markAllAsRisk">
                                    <i class="fas fa-share-square"></i> Mark All as Sent To Risk
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="markAllAsClosed">
                                    <i class="fas fa-times-circle"></i> Mark All as Closed
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-warning">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAllWarnings" class="form-check-input">
                                    </th>
                                    <th>Finding</th>
                                    <th>Project</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Age</th>
                                    <th>Created</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warningFindings as $finding): ?>
                                    <tr data-finding-id="<?php echo $finding['id']; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input warning-checkbox" value="<?php echo $finding['id']; ?>">
                                        </td>
                                        <td>
                                            <a href="pcf_finding_detail.php?id=<?php echo $finding['id']; ?>" class="text-decoration-none">
                                                <strong><?php echo htmlspecialchars($finding['name']); ?></strong>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($finding['project_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $severityClass = getSeverityClass($finding['cvss']);
                                            $severityText = getSeverityText($finding['cvss']);
                                            ?>
                                            <span class="badge <?php echo $severityClass; ?>">
                                                <?php echo $severityText; ?> (<?php echo number_format($finding['cvss'], 1); ?>)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusColor($finding['status']); ?>">
                                                <?php echo htmlspecialchars($finding['status'] ?: 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo $finding['days_old']; ?> days
                                            </span>
                                            <br><small class="text-muted"><?php echo $finding['age_reason']; ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo date('M j, Y', strtotime($finding['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-success mark-single-risk" 
                                                        data-finding-id="<?php echo $finding['id']; ?>"
                                                        title="Mark as Sent To Risk">
                                                    <i class="fas fa-share"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary mark-single-closed" 
                                                        data-finding-id="<?php echo $finding['id']; ?>"
                                                        title="Mark as Closed">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
            <i class="fas fa-filter"></i> Show Filters
        </button>
        <form method="post" class="d-inline">
            <button type="submit" name="sync_pcf" class="btn btn-success">
                <i class="fas fa-sync-alt"></i> Sync PCF Data
            </button>
        </form>
    </div>
    
    <div class="collapse" id="filterCollapse">
        <div class="card card-body">
            <form method="get" class="row g-3" action="#findings-table">
        <div class="col-md-3">
            <label for="project" class="form-label">Project</label>
            <select name="project" id="project" class="form-select">
                <option value="">All Projects</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?php echo htmlspecialchars($project['id']); ?>" 
                            <?php echo $projectFilter === $project['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($project['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="severity" class="form-label">Severity</label>
            <select name="severity" id="severity" class="form-select">
                <option value="">All Severities</option>
                <option value="critical" <?php echo $severityFilter === 'critical' ? 'selected' : ''; ?>>Critical (9.0-10.0)</option>
                <option value="high" <?php echo $severityFilter === 'high' ? 'selected' : ''; ?>>High (7.0-8.9)</option>
                <option value="medium" <?php echo $severityFilter === 'medium' ? 'selected' : ''; ?>>Medium (4.0-6.9)</option>
                <option value="low" <?php echo $severityFilter === 'low' ? 'selected' : ''; ?>>Low (0.1-3.9)</option>
                <option value="info" <?php echo $severityFilter === 'info' ? 'selected' : ''; ?>>Info (0.0)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="fixed" <?php echo $statusFilter === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                <option value="retest" <?php echo $statusFilter === 'retest' ? 'selected' : ''; ?>>Retest</option>
                <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="month" class="form-label">Creation Month</label>
            <select name="month" id="month" class="form-select">
                <option value="">All Months</option>
                <?php
                // Get available months from the database
                $monthStmt = $pdo->query("
                    SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month_year,
                           DATE_FORMAT(created_at, '%M %Y') as month_name
                    FROM pcf_findings 
                    ORDER BY month_year DESC
                ");
                $months = $monthStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($months as $month):
                ?>
                    <option value="<?php echo $month['month_year']; ?>" <?php echo $monthFilter === $month['month_year'] ? 'selected' : ''; ?>>
                        <?php echo $month['month_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <div>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="pcf_dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </div>
            </form>
        </div>
    </div>
</div>

<!-- Findings Table -->
<div class="card" id="findings-table">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-bug"></i> PCF Security Findings</h5>
            <div class="text-muted small">
                <?php
                $sortLabels = [
                    'name' => 'Finding Name',
                    'project_name' => 'Project Name',
                    'cvss' => 'Severity (CVSS)',
                    'status' => 'Status',
                    'created_at' => 'Creation Date'
                ];
                $currentSortLabel = isset($sortLabels[$sortBy]) ? $sortLabels[$sortBy] : 'Severity';
                $orderText = $sortOrder === 'asc' ? 'ascending' : 'descending';
                ?>
                <i class="fas fa-sort"></i> Sorted by: <strong><?php echo $currentSortLabel; ?></strong> (<?php echo $orderText; ?>)
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($pcfFindings)): ?>
            <p class="text-muted">No PCF findings found. Click "Sync PCF Data" to import findings.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <?php
                            // Helper function to generate sortable header
                            function getSortableHeader($column, $label, $currentSort, $currentOrder, $filters) {
                                $isActive = ($currentSort === $column);
                                $newOrder = ($isActive && $currentOrder === 'asc') ? 'desc' : 'asc';
                                $iconClass = $isActive ? ($currentOrder === 'asc' ? 'asc' : 'desc') : 'none';
                                $activeClass = $isActive ? 'active' : '';
                                
                                $url = '?sort=' . $column . '&order=' . $newOrder;
                                foreach ($filters as $key => $value) {
                                    if (!empty($value)) {
                                        $url .= '&' . $key . '=' . urlencode($value);
                                    }
                                }
                                $url .= '#findings-table';
                                
                                return '<th class="sortable-header ' . $activeClass . '" onclick="window.location.href=\'' . $url . '\'" title="Click to sort by ' . $label . '">' . 
                                       $label . '<span class="sort-icon ' . $iconClass . '"></span></th>';
                            }
                            
                            $filters = [
                                'project' => $projectFilter,
                                'severity' => $severityFilter,
                                'status' => $statusFilter,
                                'month' => $monthFilter
                            ];
                            ?>
                            <?php echo getSortableHeader('name', 'Finding', $sortBy, $sortOrder, $filters); ?>
                            <?php echo getSortableHeader('project_name', 'Project', $sortBy, $sortOrder, $filters); ?>
                            <?php echo getSortableHeader('cvss', 'Severity', $sortBy, $sortOrder, $filters); ?>
                            <?php echo getSortableHeader('cvss', 'CVSS', $sortBy, $sortOrder, $filters); ?>
                            <?php echo getSortableHeader('status', 'Status', $sortBy, $sortOrder, $filters); ?>
                            <?php echo getSortableHeader('created_at', 'Created', $sortBy, $sortOrder, $filters); ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pcfFindings as $finding): ?>
                            <tr>
                                <td>
                                    <a href="pcf_finding_detail.php?id=<?php echo $finding['id']; ?>" class="text-decoration-none">
                                        <strong><?php echo htmlspecialchars($finding['name']); ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($finding['project_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $severityClass = getSeverityClass($finding['cvss']);
                                    $severityText = getSeverityText($finding['cvss']);
                                    ?>
                                    <span class="badge <?php echo $severityClass; ?>">
                                        <?php echo $severityText; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-dark">
                                        <?php echo number_format($finding['cvss'], 1); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusColor($finding['status']); ?>">
                                        <?php echo htmlspecialchars($finding['status'] ?: 'Unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y', strtotime($finding['created_at'])); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <?php
                // Helper function to build pagination URL with all parameters
                function buildPaginationUrl($pageNum, $filters, $sortBy, $sortOrder) {
                    $url = '?page=' . $pageNum;
                    $url .= '&sort=' . urlencode($sortBy) . '&order=' . urlencode($sortOrder);
                    foreach ($filters as $key => $value) {
                        if (!empty($value)) {
                            $url .= '&' . $key . '=' . urlencode($value);
                        }
                    }
                    $url .= '#findings-table';
                    return $url;
                }
                ?>
                <nav aria-label="PCF findings pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($page - 1, $filters, $sortBy, $sortOrder); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPaginationUrl($i, $filters, $sortBy, $sortOrder); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($page + 1, $filters, $sortBy, $sortOrder); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mt-4 g-3">
    <div class="col-md-3">
        <div class="stat-card critical">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'critical'); ?></div>
                <div class="stat-label">Critical</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card high">
            <div class="stat-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'high'); ?></div>
                <div class="stat-label">High</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card medium">
            <div class="stat-icon">
                <i class="fas fa-minus-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'medium'); ?></div>
                <div class="stat-label">Medium</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card low">
            <div class="stat-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'low'); ?></div>
                <div class="stat-label">Low</div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced filter toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.querySelector('[data-bs-target="#filterCollapse"]');
    const filterCollapse = document.getElementById('filterCollapse');
    
    if (filterToggle && filterCollapse) {
        filterCollapse.addEventListener('show.bs.collapse', function() {
            filterToggle.innerHTML = '<i class="fas fa-filter"></i> Hide Filters';
        });
        
        filterCollapse.addEventListener('hide.bs.collapse', function() {
            filterToggle.innerHTML = '<i class="fas fa-filter"></i> Show Filters';
        });
        
        // Check if any filters are active and show the collapse by default
        const urlParams = new URLSearchParams(window.location.search);
        const hasActiveFilters = urlParams.get('project') || urlParams.get('severity') || 
                                urlParams.get('status') || urlParams.get('month');
        
        if (hasActiveFilters) {
            filterCollapse.classList.add('show');
            filterToggle.innerHTML = '<i class="fas fa-filter"></i> Hide Filters';
            filterToggle.setAttribute('aria-expanded', 'true');
        }
    }
    
    // Make stat cards clickable to filter by severity
    document.querySelectorAll('.stat-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const severity = this.classList.contains('critical') ? 'critical' :
                           this.classList.contains('high') ? 'high' :
                           this.classList.contains('medium') ? 'medium' :
                           this.classList.contains('low') ? 'low' : '';
            
            if (severity) {
                const url = new URL(window.location);
                url.searchParams.set('severity', severity);
                url.searchParams.delete('page'); // Reset to first page
                window.location.href = url.toString();
            }
        });
    });
    
    // Warning findings status update functionality
    const selectAllWarnings = document.getElementById('selectAllWarnings');
    const warningCheckboxes = document.querySelectorAll('.warning-checkbox');
    const markSelectedAsRiskBtn = document.getElementById('markSelectedAsRisk');
    const markSelectedAsClosedBtn = document.getElementById('markSelectedAsClosed');
    const markAllAsRiskBtn = document.getElementById('markAllAsRisk');
    const markAllAsClosedBtn = document.getElementById('markAllAsClosed');
    
    // Select all functionality
    if (selectAllWarnings) {
        selectAllWarnings.addEventListener('change', function() {
            warningCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateMarkSelectedButton();
        });
    }
    
    // Individual checkbox change
    warningCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateMarkSelectedButton);
    });
    
    // Update mark selected button state
    function updateMarkSelectedButton() {
        const checkedBoxes = document.querySelectorAll('.warning-checkbox:checked');
        const hasSelection = checkedBoxes.length > 0;
        
        if (markSelectedAsRiskBtn) {
            markSelectedAsRiskBtn.disabled = !hasSelection;
        }
        if (markSelectedAsClosedBtn) {
            markSelectedAsClosedBtn.disabled = !hasSelection;
        }
    }
    
    // Mark selected findings as sent to risk
    if (markSelectedAsRiskBtn) {
        markSelectedAsRiskBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.warning-checkbox:checked');
            const findingIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
            
            if (findingIds.length === 0) {
                alert('Please select at least one finding.');
                return;
            }
            
            if (confirm(`Are you sure you want to mark ${findingIds.length} finding(s) as "Sent To Risk"?`)) {
                updateFindingsStatus(findingIds, 'Sent To Risk');
            }
        });
    }
    
    // Mark selected findings as closed
    if (markSelectedAsClosedBtn) {
        markSelectedAsClosedBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.warning-checkbox:checked');
            const findingIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
            
            if (findingIds.length === 0) {
                alert('Please select at least one finding.');
                return;
            }
            
            if (confirm(`Are you sure you want to mark ${findingIds.length} finding(s) as "Closed"?`)) {
                updateFindingsStatus(findingIds, 'Closed');
            }
        });
    }
    
    // Mark all findings as sent to risk
    if (markAllAsRiskBtn) {
        markAllAsRiskBtn.addEventListener('click', function() {
            const allFindingIds = Array.from(warningCheckboxes).map(cb => parseInt(cb.value));
            
            if (allFindingIds.length === 0) {
                alert('No findings to update.');
                return;
            }
            
            if (confirm(`Are you sure you want to mark ALL ${allFindingIds.length} finding(s) as "Sent To Risk"?`)) {
                updateFindingsStatus(allFindingIds, 'Sent To Risk');
            }
        });
    }
    
    // Mark all findings as closed
    if (markAllAsClosedBtn) {
        markAllAsClosedBtn.addEventListener('click', function() {
            const allFindingIds = Array.from(warningCheckboxes).map(cb => parseInt(cb.value));
            
            if (allFindingIds.length === 0) {
                alert('No findings to update.');
                return;
            }
            
            if (confirm(`Are you sure you want to mark ALL ${allFindingIds.length} finding(s) as "Closed"?`)) {
                updateFindingsStatus(allFindingIds, 'Closed');
            }
        });
    }
    
    // Individual finding mark as sent to risk
    document.querySelectorAll('.mark-single-risk').forEach(button => {
        button.addEventListener('click', function() {
            const findingId = parseInt(this.getAttribute('data-finding-id'));
            const findingName = this.closest('tr').querySelector('strong').textContent;
            
            if (confirm(`Are you sure you want to mark "${findingName}" as "Sent To Risk"?`)) {
                updateFindingsStatus([findingId], 'Sent To Risk');
            }
        });
    });
    
    // Individual finding mark as closed
    document.querySelectorAll('.mark-single-closed').forEach(button => {
        button.addEventListener('click', function() {
            const findingId = parseInt(this.getAttribute('data-finding-id'));
            const findingName = this.closest('tr').querySelector('strong').textContent;
            
            if (confirm(`Are you sure you want to mark "${findingName}" as "Closed"?`)) {
                updateFindingsStatus([findingId], 'Closed');
            }
        });
    });
    
    // Function to update findings status via AJAX
    function updateFindingsStatus(findingIds, status = 'Sent To Risk') {
        // Show loading state
        const buttons = document.querySelectorAll('#markSelectedAsRisk, #markSelectedAsClosed, #markAllAsRisk, #markAllAsClosed, .mark-single-risk, .mark-single-closed');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        });
        
        fetch('ajax/update_finding_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                finding_ids: findingIds,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const message = findingIds.length === 1 ? 
                    `Finding marked as "${status}" successfully!` :
                    `${data.success_count} finding(s) marked as "${status}" successfully!`;
                
                alert(message);
                
                // Reload page to refresh the warning list
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
                // Restore button states
                restoreButtonStates();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the findings.');
            restoreButtonStates();
        });
    }
    
    // Function to restore button states
    function restoreButtonStates() {
        const markSelectedAsRiskBtn = document.getElementById('markSelectedAsRisk');
        const markSelectedAsClosedBtn = document.getElementById('markSelectedAsClosed');
        const markAllAsRiskBtn = document.getElementById('markAllAsRisk');
        const markAllAsClosedBtn = document.getElementById('markAllAsClosed');
        
        if (markSelectedAsRiskBtn) {
            markSelectedAsRiskBtn.innerHTML = '<i class="fas fa-share"></i> Mark Selected as Sent To Risk';
        }
        if (markSelectedAsClosedBtn) {
            markSelectedAsClosedBtn.innerHTML = '<i class="fas fa-times-circle"></i> Mark Selected as Closed';
        }
        updateMarkSelectedButton();
        
        if (markAllAsRiskBtn) {
            markAllAsRiskBtn.disabled = false;
            markAllAsRiskBtn.innerHTML = '<i class="fas fa-share-square"></i> Mark All as Sent To Risk';
        }
        if (markAllAsClosedBtn) {
            markAllAsClosedBtn.disabled = false;
            markAllAsClosedBtn.innerHTML = '<i class="fas fa-times-circle"></i> Mark All as Closed';
        }
        
        document.querySelectorAll('.mark-single-risk').forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-share"></i>';
        });
        
        document.querySelectorAll('.mark-single-closed').forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-times-circle"></i>';
        });
    }
    
    // Enhanced scroll position management
    function saveScrollPosition() {
        const findingsTable = document.getElementById('findings-table');
        if (findingsTable) {
            const rect = findingsTable.getBoundingClientRect();
            const isTableVisible = rect.top < window.innerHeight && rect.bottom > 0;
            
            if (isTableVisible) {
                // If table is visible, save position relative to table
                sessionStorage.setItem('pcf_scroll_to_table', 'true');
            } else {
                // Otherwise save absolute position
                sessionStorage.setItem('pcf_scroll_position', window.pageYOffset);
            }
        }
    }
    
    function restoreScrollPosition() {
        // Check if we should scroll to table
        if (sessionStorage.getItem('pcf_scroll_to_table')) {
            sessionStorage.removeItem('pcf_scroll_to_table');
            setTimeout(() => {
                const findingsTable = document.getElementById('findings-table');
                if (findingsTable) {
                    findingsTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        } else {
            // Restore saved position
            const savedPosition = sessionStorage.getItem('pcf_scroll_position');
            if (savedPosition) {
                setTimeout(() => {
                    window.scrollTo({ top: parseInt(savedPosition), behavior: 'smooth' });
                }, 100);
                sessionStorage.removeItem('pcf_scroll_position');
            }
        }
    }
    
    // Restore scroll position on page load
    restoreScrollPosition();
    
    // Enhanced sorting functionality
    document.querySelectorAll('.sortable-header').forEach(header => {
        header.addEventListener('click', function(e) {
            // Save current scroll position before navigating
            saveScrollPosition();
            
            // Add loading state
            const originalContent = this.innerHTML;
            this.style.opacity = '0.6';
            this.style.pointerEvents = 'none';
            
            // Add a small delay to show the loading state
            setTimeout(() => {
                window.location.href = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            }, 100);
        });
        
        // Add hover effect for better UX
        header.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.backgroundColor = 'rgba(0,123,255,0.1)';
            }
        });
        
        header.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.backgroundColor = '';
            }
        });
    });
    
    // Also save scroll position for filter form submissions
    const filterForm = document.querySelector('form[method="get"]');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            saveScrollPosition();
        });
    }
    
    // Save scroll position for pagination links
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function() {
            saveScrollPosition();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>