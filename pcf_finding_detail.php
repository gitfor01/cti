<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/pcf_functions.php';
// Require authentication for accessing the dashboard
require_once 'auth.php';

// Get finding ID from URL
$findingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$findingId) {
    header('Location: pcf_dashboard.php');
    exit;
}

// Get finding details
$stmt = $pdo->prepare("SELECT * FROM pcf_findings WHERE id = :id");
$stmt->execute([':id' => $findingId]);
$finding = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$finding) {
    header('Location: pcf_dashboard.php');
    exit;
}

$pageTitle = 'PT Finding: ' . htmlspecialchars($finding['name']) . ' - AMT';
include 'includes/header.php';
?>

<style>
.severity-critical { background-color: #dc3545; color: white; }
.severity-high { background-color: #fd7e14; color: white; }
.severity-medium { background-color: #ffc107; color: black; }
.severity-low { background-color: #28a745; color: white; }
.severity-info { background-color: #17a2b8; color: white; }
.detail-section { margin-bottom: 30px; }
.detail-label { font-weight: bold; color: #495057; }
.code-block { background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
</style>

<div class="row mb-4">
    <div class="col-md-8">
        <h1><i class="fas fa-bug"></i> <?php echo htmlspecialchars($finding['name']); ?></h1>
        <p class="text-muted">PT Finding Details</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="pcf_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Main Finding Information -->
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Finding Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 detail-label">Name:</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($finding['name']); ?></div>
                </div>
                
                <?php if (!empty($finding['description'])): ?>
                <div class="row mb-3">
                    <div class="col-md-3 detail-label">Description:</div>
                    <div class="col-md-9">
                        <div class="code-block">
                            <?php echo nl2br(htmlspecialchars($finding['description'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($finding['url_path'])): ?>
                <div class="row mb-3">
                    <div class="col-md-3 detail-label">URL/Path:</div>
                    <div class="col-md-9">
                        <code><?php echo htmlspecialchars($finding['url_path']); ?></code>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-3 detail-label">Project:</div>
                    <div class="col-md-9">
                        <span class="badge bg-secondary fs-6">
                            <?php echo htmlspecialchars($finding['project_name']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 detail-label">Type:</div>
                    <div class="col-md-9">
                        <span class="badge bg-info">
                            <?php echo htmlspecialchars($finding['type']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Technical Details -->
        <?php if (!empty($finding['technical'])): ?>
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-code"></i> Technical Details</h5>
            </div>
            <div class="card-body">
                <div class="code-block">
                    <?php echo nl2br(htmlspecialchars($finding['technical'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Risk Assessment -->
        <?php if (!empty($finding['risks'])): ?>
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-exclamation-triangle"></i> Risk Assessment</h5>
            </div>
            <div class="card-body">
                <div class="code-block">
                    <?php echo nl2br(htmlspecialchars($finding['risks'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Fix/Remediation -->
        <?php if (!empty($finding['fix_description'])): ?>
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-tools"></i> Remediation</h5>
            </div>
            <div class="card-body">
                <div class="code-block">
                    <?php echo nl2br(htmlspecialchars($finding['fix_description'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- References -->
        <?php if (!empty($finding['references'])): ?>
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-link"></i> References</h5>
            </div>
            <div class="card-body">
                <div class="code-block">
                    <?php echo nl2br(htmlspecialchars($finding['references'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Parameters -->
        <?php if (!empty($finding['param'])): ?>
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-cog"></i> Parameters</h5>
            </div>
            <div class="card-body">
                <div class="code-block">
                    <?php echo nl2br(htmlspecialchars($finding['param'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Severity and Status -->
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Severity & Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="detail-label mb-2">Severity:</div>
                    <?php 
                    $severityClass = getSeverityClass($finding['cvss']);
                    $severityText = getSeverityText($finding['cvss']);
                    ?>
                    <span class="badge <?php echo $severityClass; ?> fs-6">
                        <?php echo $severityText; ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <div class="detail-label mb-2">CVSS Score:</div>
                    <span class="badge bg-dark fs-6">
                        <?php echo number_format($finding['cvss'], 1); ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <div class="detail-label mb-2">Status:</div>
                    <span class="badge bg-<?php echo getStatusColor($finding['status']); ?> fs-6">
                        <?php echo htmlspecialchars($finding['status'] ?: 'Unknown'); ?>
                    </span>
                </div>
                
                <?php if (!empty($finding['cwe']) && $finding['cwe'] != 0): ?>
                <div class="mb-3">
                    <div class="detail-label mb-2">CWE:</div>
                    <span class="badge bg-info fs-6">CWE-<?php echo $finding['cwe']; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($finding['cve'])): ?>
                <div class="mb-3">
                    <div class="detail-label mb-2">CVE:</div>
                    <span class="badge bg-warning text-dark fs-6"><?php echo htmlspecialchars($finding['cve']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Project Information -->
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-project-diagram"></i> Project Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="detail-label mb-2">Project Name:</div>
                    <div><?php echo htmlspecialchars($finding['project_name']); ?></div>
                </div>
                
                <?php if (!empty($finding['project_description'])): ?>
                <div class="mb-3">
                    <div class="detail-label mb-2">Project Description:</div>
                    <div class="text-muted small">
                        <?php echo nl2br(htmlspecialchars($finding['project_description'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($finding['start_date'])): ?>
                <div class="mb-3">
                    <div class="detail-label mb-2">Project Start:</div>
                    <div><?php echo date('M j, Y', strtotime($finding['start_date'])); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($finding['end_date'])): ?>
                <div class="mb-3">
                    <div class="detail-label mb-2">Project End:</div>
                    <div><?php echo date('M j, Y', strtotime($finding['end_date'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Timestamps -->
        <div class="card detail-section">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Timestamps</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="detail-label mb-2">Synced to CTI:</div>
                    <div><?php echo date('M j, Y H:i:s', strtotime($finding['created_at'])); ?></div>
                </div>
                
                <div class="mb-3">
                    <div class="detail-label mb-2">Last Updated:</div>
                    <div><?php echo date('M j, Y H:i:s', strtotime($finding['updated_at'])); ?></div>
                </div>
                
                <div class="mb-3">
                    <div class="detail-label mb-2">PCF ID:</div>
                    <div><code><?php echo htmlspecialchars($finding['pcf_id']); ?></code></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>