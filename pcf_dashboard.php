<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/pcf_functions.php';
// Require authentication for accessing the dashboard
require_once 'auth.php';

// Handle sync request
if (isset($_POST['sync_pcf'])) {
    $syncResult = syncPcfFindings($pdo);
    if ($syncResult['success']) {
        $syncMessage = "Successfully synced findings from PCF: ";
        $syncMessage .= "{$syncResult['inserted']} new, ";
        $syncMessage .= "{$syncResult['updated']} updated, ";
        $syncMessage .= "{$syncResult['deleted']} deleted.";
    } else {
        $syncMessage = "Error syncing PCF data: {$syncResult['error']}";
    }
}

// Get PCF findings with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get filter parameters - handle both single values and arrays
$projectFilter = isset($_GET['project']) ? $_GET['project'] : '';
$severityFilter = isset($_GET['severity']) ? $_GET['severity'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$monthFilter = isset($_GET['month']) ? $_GET['month'] : '';
$yearFilter = isset($_GET['year']) ? $_GET['year'] : date('Y'); // Default to current year

// Convert single values to arrays if needed, and filter out empty values
if (!empty($projectFilter)) {
    $projectFilter = is_array($projectFilter) ? array_filter($projectFilter) : [$projectFilter];
    $projectFilter = empty($projectFilter) ? '' : $projectFilter;
}
if (!empty($severityFilter)) {
    $severityFilter = is_array($severityFilter) ? array_filter($severityFilter) : [$severityFilter];
    $severityFilter = empty($severityFilter) ? '' : $severityFilter;
}
if (!empty($statusFilter)) {
    $statusFilter = is_array($statusFilter) ? array_filter($statusFilter) : [$statusFilter];
    $statusFilter = empty($statusFilter) ? '' : $statusFilter;
}
if (!empty($monthFilter)) {
    $monthFilter = is_array($monthFilter) ? array_filter($monthFilter) : [$monthFilter];
    $monthFilter = empty($monthFilter) ? '' : $monthFilter;
}

// Get sorting parameters
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'cvss';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'desc';

$pcfFindings = getPcfFindings($pdo, $limit, $offset, $projectFilter, $severityFilter, $statusFilter, $monthFilter, $sortBy, $sortOrder, $yearFilter);
$totalFindings = getPcfFindingsCount($pdo, $projectFilter, $severityFilter, $statusFilter, $monthFilter, $yearFilter);
$totalPages = ceil($totalFindings / $limit);

// Get unique projects for filter dropdown
$projects = getPcfProjects($pdo);

// Get unique status values for filter dropdown
$statusValues = getPcfStatusValues($pdo);

// Get unique creation months for filter dropdown
$creationMonths = getPcfCreationMonths($pdo);

// Get last sync time
$lastSync = getLastSyncTime($pdo);

// Get warning findings
$warningFindings = getWarningFindings($pdo);
$warningCount = count($warningFindings);

$pageTitle = 'PT Dashboard - AMT';
include 'includes/header.php';
?>

<style>
.severity-critical { background-color: #8b0000; color: white; } /* Dark red */
.severity-high { background-color: #dc3545; color: white; } /* Red */
.severity-medium { background-color: #fd7e14; color: white; } /* Orange */
.severity-low { background-color: #28a745; color: white; } /* Green */
.severity-info { background-color: #17a2b8; color: white; } /* Light blue */

/* Custom badge colors for dropdown */
.badge-critical { background-color: #8b0000 !important; color: white !important; } /* Dark red */
.badge-high { background-color: #dc3545 !important; color: white !important; } /* Red */
.badge-medium { background-color: #fd7e14 !important; color: white !important; } /* Orange */
.badge-low { background-color: #28a745 !important; color: white !important; } /* Green */
.badge-info { background-color: #17a2b8 !important; color: white !important; } /* Light blue */
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
    background: linear-gradient(135deg, #8b0000, #660000);
    color: white;
}
.stat-card.critical .stat-number {
    color: #8b0000;
}

.stat-card.high .stat-icon {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}
.stat-card.high .stat-number {
    color: #dc3545;
}

.stat-card.medium .stat-icon {
    background: linear-gradient(135deg, #fd7e14, #e8690b);
    color: white;
}
.stat-card.medium .stat-number {
    color: #fd7e14;
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

/* Checkbox dropdown styling */
.dropdown-menu {
    min-width: 100%;
    position: absolute !important;
    overflow: visible !important;
    z-index: 1050;
}

.dropdown-menu .form-check {
    margin-bottom: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
    position: relative;
    display: flex;
    align-items: center;
    overflow: visible;
}

.dropdown-menu .form-check:hover {
    background-color: #f8f9fa;
}

/* Selected item styling */
.dropdown-menu .form-check:has(input:checked) {
    background-color: #e3f2fd;
    border: 1px solid #2196f3;
    box-shadow: 0 1px 3px rgba(33, 150, 243, 0.2);
}

.dropdown-menu .form-check:has(input:checked):hover {
    background-color: #bbdefb;
}

.dropdown-menu .form-check:has(input:checked) .form-check-label {
    color: #1976d2;
    font-weight: 500;
}

/* Fallback for browsers that don't support :has() */
.dropdown-menu .form-check.selected {
    background-color: #e3f2fd;
    border: 1px solid #2196f3;
    box-shadow: 0 1px 3px rgba(33, 150, 243, 0.2);
}

.dropdown-menu .form-check.selected:hover {
    background-color: #bbdefb;
}

.dropdown-menu .form-check.selected .form-check-label {
    color: #1976d2;
    font-weight: 500;
}

.dropdown-menu .form-check-input {
    position: relative !important;
    margin-top: 0 !important;
    margin-right: 8px;
    margin-left: 0 !important;
    flex-shrink: 0;
}

.dropdown-menu .form-check-input:checked {
    background-color: #2196f3;
    border-color: #2196f3;
}

.dropdown-menu .form-check-label {
    cursor: pointer;
    flex: 1;
    padding-left: 0;
    margin-bottom: 0;
    display: flex;
    align-items: center;
}

/* Ensure dropdown items don't clip content */
.dropdown-menu .dropdown-item,
.dropdown-menu .form-check {
    position: relative;
    overflow: visible;
}

.dropdown-toggle .filter-count {
    font-size: 0.75rem;
}

.dropdown-menu hr {
    margin: 0.5rem 0;
}

.dropdown-menu .btn-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Filter section improvements */
.filter-section .form-text {
    font-size: 0.75rem;
    margin-top: 2px;
}

/* Clear filters button */
.clear-filters {
    margin-left: 10px;
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
                        from completed projects or older than 1 month <?php echo $warningCount > 1 ? 'have' : 'has'; ?> not had risk raised.
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
                                    <i class="fas fa-share"></i> Mark Selected as Risk Raised
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" id="markSelectedAsClosed" disabled>
                                    <i class="fas fa-times-circle"></i> Mark Selected as Closed
                                </button>
                            </div>
                            <div class="btn-group ms-2" role="group">
                                <button type="button" class="btn btn-sm btn-outline-success" id="markAllAsRisk">
                                    <i class="fas fa-share-square"></i> Mark All as Risk Raised
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
                                                        title="Mark as Risk Raised">
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

<!-- Year Navigation -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex justify-content-center align-items-center">
            <div class="d-flex align-items-center">
                <!-- Previous Year Arrow -->
                <?php 
                $prevYear = $yearFilter - 1;
                $currentFilters = http_build_query(array_filter([
                    'project' => $projectFilter,
                    'severity' => $severityFilter,
                    'status' => $statusFilter,
                    'month' => $monthFilter,
                    'sort' => $sortBy,
                    'order' => $sortOrder,
                    'year' => $prevYear
                ]));
                ?>
                <a href="?<?php echo $currentFilters; ?>" class="btn btn-outline-secondary btn-sm me-3" title="Previous Year (<?php echo $prevYear; ?>)">
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <!-- Current Year Display -->
                <h5 class="mb-0 mx-3">
                    <i class="fas fa-calendar-alt text-primary"></i> 
                    <span class="badge bg-primary"><?php echo $yearFilter; ?></span>
                </h5>
                
                <!-- Next Year Arrow -->
                <?php 
                $nextYear = $yearFilter + 1;
                $nextFilters = http_build_query(array_filter([
                    'project' => $projectFilter,
                    'severity' => $severityFilter,
                    'status' => $statusFilter,
                    'month' => $monthFilter,
                    'sort' => $sortBy,
                    'order' => $sortOrder,
                    'year' => $nextYear
                ]));
                ?>
                <a href="?<?php echo $nextFilters; ?>" class="btn btn-outline-secondary btn-sm ms-3" title="Next Year (<?php echo $nextYear; ?>)">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

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
                <!-- Hidden input to preserve year filter -->
                <input type="hidden" name="year" value="<?php echo htmlspecialchars($yearFilter); ?>">
        <!-- Project Filter -->
        <div class="col-md-3">
            <label class="form-label">Project</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="projectDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="filter-text">Select Projects</span>
                    <span class="badge bg-primary ms-2 filter-count" style="display: none;">0</span>
                </button>
                <div class="dropdown-menu w-100 p-2" aria-labelledby="projectDropdown" style="max-height: 300px; overflow-y: auto;">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1 select-all-btn">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary clear-all-btn">Clear All</button>
                    </div>
                    <hr class="my-2">
                    <?php foreach ($projects as $project): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="project[]" value="<?php echo htmlspecialchars($project['id']); ?>" 
                                   id="project_<?php echo htmlspecialchars($project['id']); ?>"
                                   <?php echo (is_array($projectFilter) && in_array($project['id'], $projectFilter)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="project_<?php echo htmlspecialchars($project['id']); ?>">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Severity Filter -->
        <div class="col-md-3">
            <label class="form-label">Severity</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="severityDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="filter-text">Select Severity</span>
                    <span class="badge bg-primary ms-2 filter-count" style="display: none;">0</span>
                </button>
                <div class="dropdown-menu w-100 p-2" aria-labelledby="severityDropdown">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1 select-all-btn">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary clear-all-btn">Clear All</button>
                    </div>
                    <hr class="my-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="severity[]" value="critical" id="severity_critical"
                               <?php echo (is_array($severityFilter) && in_array('critical', $severityFilter)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="severity_critical">
                            <span class="badge badge-critical me-2">Critical</span> (9.0-10.0)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="severity[]" value="high" id="severity_high"
                               <?php echo (is_array($severityFilter) && in_array('high', $severityFilter)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="severity_high">
                            <span class="badge badge-high me-2">High</span> (7.0-8.9)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="severity[]" value="medium" id="severity_medium"
                               <?php echo (is_array($severityFilter) && in_array('medium', $severityFilter)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="severity_medium">
                            <span class="badge badge-medium me-2">Medium</span> (4.0-6.9)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="severity[]" value="low" id="severity_low"
                               <?php echo (is_array($severityFilter) && in_array('low', $severityFilter)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="severity_low">
                            <span class="badge badge-low me-2">Low</span> (0.1-3.9)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="severity[]" value="info" id="severity_info"
                               <?php echo (is_array($severityFilter) && in_array('info', $severityFilter)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="severity_info">
                            <span class="badge badge-info me-2">Info</span> (0.0)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="filter-text">Select Status</span>
                    <span class="badge bg-primary ms-2 filter-count" style="display: none;">0</span>
                </button>
                <div class="dropdown-menu w-100 p-2" aria-labelledby="statusDropdown">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1 select-all-btn">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary clear-all-btn">Clear All</button>
                    </div>
                    <hr class="my-2">
                    <?php foreach ($statusValues as $status): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status[]" value="<?php echo htmlspecialchars($status); ?>" 
                                   id="status_<?php echo htmlspecialchars(str_replace([' ', '-', '.'], '_', strtolower($status))); ?>"
                                   <?php echo (is_array($statusFilter) && in_array($status, $statusFilter)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status_<?php echo htmlspecialchars(str_replace([' ', '-', '.'], '_', strtolower($status))); ?>">
                                <?php echo htmlspecialchars(ucfirst($status)); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Month Filter -->
        <div class="col-md-2">
            <label class="form-label">Creation Month</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="monthDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="filter-text">Select Months</span>
                    <span class="badge bg-primary ms-2 filter-count" style="display: none;">0</span>
                </button>
                <div class="dropdown-menu w-100 p-2" aria-labelledby="monthDropdown" style="max-height: 300px; overflow-y: auto;">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1 select-all-btn">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary clear-all-btn">Clear All</button>
                    </div>
                    <hr class="my-2">
                    <?php foreach ($creationMonths as $month): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="month[]" value="<?php echo htmlspecialchars($month['month_year']); ?>" 
                                   id="month_<?php echo str_replace('-', '_', $month['month_year']); ?>"
                                   <?php echo (is_array($monthFilter) && in_array($month['month_year'], $monthFilter)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="month_<?php echo str_replace('-', '_', $month['month_year']); ?>">
                                <?php echo htmlspecialchars($month['month_name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <div>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="pcf_dashboard.php" class="btn btn-outline-secondary clear-filters">
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
                                        if (is_array($value)) {
                                            // Handle array values (multiple selections)
                                            foreach ($value as $item) {
                                                $url .= '&' . $key . '[]=' . urlencode($item);
                                            }
                                        } else {
                                            // Handle single values
                                            $url .= '&' . $key . '=' . urlencode($value);
                                        }
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
                                'month' => $monthFilter,
                                'year' => $yearFilter
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
                // Define filters array for pagination
                $filters = [
                    'project' => $projectFilter,
                    'severity' => $severityFilter,
                    'status' => $statusFilter,
                    'month' => $monthFilter,
                    'year' => $yearFilter
                ];
                
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
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'critical', $yearFilter); ?></div>
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
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'high', $yearFilter); ?></div>
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
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'medium', $yearFilter); ?></div>
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
                <div class="stat-number"><?php echo getPcfFindingsCountBySeverity($pdo, 'low', $yearFilter); ?></div>
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
    
    // Mark selected findings as risk raised
    if (markSelectedAsRiskBtn) {
        markSelectedAsRiskBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.warning-checkbox:checked');
            const findingIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
            
            if (findingIds.length === 0) {
                alert('Please select at least one finding.');
                return;
            }
            
            if (confirm(`Are you sure you want to mark ${findingIds.length} finding(s) as "Risk Raised"?`)) {
                updateFindingsStatus(findingIds, 'Risk Raised');
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
    
    // Mark all findings as risk raised
    if (markAllAsRiskBtn) {
        markAllAsRiskBtn.addEventListener('click', function() {
            const allFindingIds = Array.from(warningCheckboxes).map(cb => parseInt(cb.value));
            
            if (allFindingIds.length === 0) {
                alert('No findings to update.');
                return;
            }
            
            if (confirm(`Are you sure you want to mark ALL ${allFindingIds.length} finding(s) as "Risk Raised"?`)) {
                updateFindingsStatus(allFindingIds, 'Risk Raised');
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
    
    // Individual finding mark as risk raised
    document.querySelectorAll('.mark-single-risk').forEach(button => {
        button.addEventListener('click', function() {
            const findingId = parseInt(this.getAttribute('data-finding-id'));
            const findingName = this.closest('tr').querySelector('strong').textContent;
            
            if (confirm(`Are you sure you want to mark "${findingName}" as "Risk Raised"?`)) {
                updateFindingsStatus([findingId], 'Risk Raised');
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
    function updateFindingsStatus(findingIds, status = 'Risk Raised') {
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
            markSelectedAsRiskBtn.innerHTML = '<i class="fas fa-share"></i> Mark Selected as Risk Raised';
        }
        if (markSelectedAsClosedBtn) {
            markSelectedAsClosedBtn.innerHTML = '<i class="fas fa-times-circle"></i> Mark Selected as Closed';
        }
        updateMarkSelectedButton();
        
        if (markAllAsRiskBtn) {
            markAllAsRiskBtn.disabled = false;
            markAllAsRiskBtn.innerHTML = '<i class="fas fa-share-square"></i> Mark All as Risk Raised';
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

    // Enhanced checkbox dropdown functionality
    function updateDropdownText(dropdown) {
        const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
        const button = dropdown.querySelector('.dropdown-toggle');
        const filterText = button.querySelector('.filter-text');
        const filterCount = button.querySelector('.filter-count');
        const count = checkboxes.length;
        
        if (count > 0) {
            filterCount.textContent = count;
            filterCount.style.display = 'inline';
            button.classList.add('btn-primary');
            button.classList.remove('btn-outline-secondary');
            
            // Update text based on selections
            if (count === 1) {
                const selectedLabel = dropdown.querySelector('input[type="checkbox"]:checked').nextElementSibling.textContent.trim();
                filterText.textContent = selectedLabel.length > 20 ? selectedLabel.substring(0, 20) + '...' : selectedLabel;
            } else {
                const originalText = filterText.getAttribute('data-original') || filterText.textContent;
                filterText.textContent = originalText;
            }
        } else {
            filterCount.style.display = 'none';
            button.classList.remove('btn-primary');
            button.classList.add('btn-outline-secondary');
            const originalText = filterText.getAttribute('data-original') || filterText.textContent;
            filterText.textContent = originalText;
        }
    }
    
    // Initialize dropdown functionality
    document.querySelectorAll('.dropdown').forEach(function(dropdown) {
        const button = dropdown.querySelector('.dropdown-toggle');
        const filterText = button.querySelector('.filter-text');
        const selectAllBtn = dropdown.querySelector('.select-all-btn');
        const clearAllBtn = dropdown.querySelector('.clear-all-btn');
        const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
        
        // Store original text
        if (!filterText.getAttribute('data-original')) {
            filterText.setAttribute('data-original', filterText.textContent);
        }
        
        // Initialize display
        updateDropdownText(dropdown);
        
        // Handle checkbox changes
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                // Update visual styling for selected items
                const formCheck = this.closest('.form-check');
                if (this.checked) {
                    formCheck.classList.add('selected');
                } else {
                    formCheck.classList.remove('selected');
                }
                updateDropdownText(dropdown);
            });
            
            // Initialize selected state on page load
            const formCheck = checkbox.closest('.form-check');
            if (checkbox.checked) {
                formCheck.classList.add('selected');
            }
        });
        
        // Handle Select All button
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                    checkbox.closest('.form-check').classList.add('selected');
                });
                updateDropdownText(dropdown);
            });
        }
        
        // Handle Clear All button
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                    checkbox.closest('.form-check').classList.remove('selected');
                });
                updateDropdownText(dropdown);
            });
        }
        
        // Prevent dropdown from closing when clicking inside
        dropdown.querySelector('.dropdown-menu').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>