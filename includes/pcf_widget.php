<?php
/**
 * PCF Status Widget
 * 
 * This widget can be included in other pages to show PCF sync status
 */

// Only show if PCF integration is set up
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'pcf_findings'");
    $pcfEnabled = $stmt->fetch() ? true : false;
} catch (Exception $e) {
    $pcfEnabled = false;
}

if ($pcfEnabled) {
    try {
        // Get PCF statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pcf_findings");
        $totalFindings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as critical FROM pcf_findings WHERE cvss >= 9.0");
        $criticalFindings = $stmt->fetch(PDO::FETCH_ASSOC)['critical'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as high FROM pcf_findings WHERE cvss >= 7.0 AND cvss < 9.0");
        $highFindings = $stmt->fetch(PDO::FETCH_ASSOC)['high'];
        
        $lastSync = getLastSyncTime($pdo);
        $syncAge = $lastSync ? time() - strtotime($lastSync) : null;
        
        ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-shield-alt"></i> PT Integration Status</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-primary"><?php echo number_format($totalFindings); ?></h3>
                            <small class="text-muted">Total Findings</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-danger"><?php echo number_format($criticalFindings); ?></h3>
                            <small class="text-muted">Critical</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-warning"><?php echo number_format($highFindings); ?></h3>
                            <small class="text-muted">High</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <?php if ($syncAge !== null): ?>
                                <?php if ($syncAge < 3600): ?>
                                    <h3 class="text-success"><i class="fas fa-check-circle"></i></h3>
                                    <small class="text-muted">Synced <?php echo round($syncAge / 60); ?>m ago</small>
                                <?php elseif ($syncAge < 86400): ?>
                                    <h3 class="text-warning"><i class="fas fa-clock"></i></h3>
                                    <small class="text-muted">Synced <?php echo round($syncAge / 3600); ?>h ago</small>
                                <?php else: ?>
                                    <h3 class="text-danger"><i class="fas fa-exclamation-triangle"></i></h3>
                                    <small class="text-muted">Synced <?php echo round($syncAge / 86400); ?>d ago</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <h3 class="text-secondary"><i class="fas fa-question-circle"></i></h3>
                                <small class="text-muted">Never synced</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12 text-center">
                        <a href="pcf_dashboard.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-tachometer-alt"></i> View PT Dashboard
                        </a>
                        <form method="post" action="pcf_dashboard.php" class="d-inline">
                            <button type="submit" name="sync_pcf" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-sync-alt"></i> Sync Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
    } catch (Exception $e) {
        // Silently fail - don't show widget if there's an error
    }
}
?>