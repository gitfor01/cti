<?php
/**
 * IP Lookup page
 *
 * Allows an authenticated user to enter an IP address and determine
 * which team, if any, is responsible for it based on configured
 * ranges. Requires authentication. Displays the team name if a
 * mapping is found, otherwise informs the user that no mapping exists.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth.php';

$ipInput = '';
$results = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipInput = isset($_POST['ip_address']) ? trim($_POST['ip_address']) : '';
    if ($ipInput === '') {
        $error = 'Please enter one or more IP addresses.';
    } else {
        $results = getTeamsByIpInput($pdo, $ipInput);
        if (empty($results)) {
            $error = 'No valid IP addresses found in input.';
        }
    }
}

$pageTitle = 'IP Lookup - AMT';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-network-wired"></i> IP to Team Mapping</h1>
        <p class="text-muted">Enter any combination of IPs, ranges, or CIDR blocks (space, comma, or line separated) to find associated teams</p>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> IP Lookup</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Addresses</label>
                        <textarea name="ip_address" id="ip_address" class="form-control" rows="5" 
                                  placeholder="Enter any combination (space, comma, or line separated):&#10;&#10;Single IPs: 192.168.1.1, 10.0.0.1&#10;IP ranges: 192.168.1.1-192.168.1.50&#10;CIDR blocks: 10.10.10.0/24, 10.10.20.0/24&#10;Mixed: 192.168.1.1, 10.10.10.0/24, 172.16.1.1-172.16.1.10"><?php echo htmlspecialchars($ipInput); ?></textarea>
                        <div class="form-text">
                            <strong>All formats supported:</strong><br>
                            • <strong>Single IPs:</strong> <code>192.168.1.1, 10.0.0.1</code><br>
                            • <strong>IP ranges:</strong> <code>192.168.1.1-192.168.1.50</code> (efficient range storage)<br>
                            • <strong>CIDR blocks:</strong> <code>10.10.10.0/24, 10.10.20.0/24</code> (efficient range storage)<br>
                            • <strong>Mixed formats:</strong> <code>192.168.1.1, 10.10.10.0/24, 172.16.1.1-172.16.1.10</code><br>
                            <small class="text-info"><i class="fas fa-info-circle"></i> All formats work together. Ranges and CIDR blocks are stored as optimized ranges for better performance.</small>
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Lookup Teams
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> How it works</h5>
            </div>
            <div class="card-body">
                <h6>Input Examples:</h6>
                <ul class="small">
                    <li><code>192.168.1.1</code> - Single IP</li>
                    <li><code>10.10.10.0/24, 10.10.20.0/24</code> - Multiple CIDR blocks</li>
                    <li><code>10.0.0.1-10.0.0.50</code> - IP range</li>
                    <li><code>192.168.1.1, 10.10.10.0/24, 172.16.1.1-172.16.1.10</code> - Mixed formats</li>
                    <li>All formats work together in any combination!</li>
                </ul>
                
                <hr>
                
                <h6>Tips:</h6>
                <ul class="small">
                    <li>Use CIDR notation for network blocks</li>
                    <li>Ranges show all overlapping team mappings</li>
                    <li>Only IPv4 addresses are supported</li>
                    <li>Invalid entries will be shown with errors</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($results !== null && !empty($results)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Lookup Results</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($results as $result): ?>
                        <div class="mb-3 p-3 border rounded">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-1">
                                        <?php 
                                        $typeIcon = '';
                                        switch ($result['type']) {
                                            case 'single': $typeIcon = 'fas fa-dot-circle'; break;
                                            case 'cidr': $typeIcon = 'fas fa-network-wired'; break;
                                            case 'range': $typeIcon = 'fas fa-arrows-alt-h'; break;
                                            case 'invalid': $typeIcon = 'fas fa-exclamation-triangle text-danger'; break;
                                        }
                                        ?>
                                        <i class="<?php echo $typeIcon; ?>"></i>
                                        <?php echo htmlspecialchars($result['original']); ?>
                                    </h6>
                                    
                                    <?php if ($result['type'] !== 'invalid' && $result['type'] !== 'single'): ?>
                                        <small class="text-muted">
                                            Range: <?php echo htmlspecialchars($result['start_ip']); ?> - <?php echo htmlspecialchars($result['end_ip']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <?php if ($result['type'] === 'invalid'): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times"></i> <?php echo htmlspecialchars($result['error']); ?>
                                        </span>
                                    <?php elseif ($result['type'] === 'single'): ?>
                                        <?php if ($result['found']): ?>
                                            <?php foreach ($result['teams'] as $team): ?>
                                                <span class="badge bg-success me-1">
                                                    <i class="fas fa-check"></i> <?php echo htmlspecialchars($team); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($result['teams']) > 1): ?>
                                                <br><small class="text-muted">
                                                    Found <?php echo count($result['teams']); ?> overlapping team(s)
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-question-circle"></i> No team mapping found
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($result['found']): ?>
                                            <?php foreach ($result['teams'] as $team): ?>
                                                <span class="badge bg-success me-1">
                                                    <i class="fas fa-check"></i> <?php echo htmlspecialchars($team); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <br><small class="text-muted">
                                                Found <?php echo count($result['teams']); ?> overlapping team(s)
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-question-circle"></i> No team mappings found
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <?php 
                            $validCount = count(array_filter($results, function($r) { return $r['type'] !== 'invalid'; }));
                            $foundCount = count(array_filter($results, function($r) { return isset($r['found']) && $r['found']; }));
                            $invalidCount = count(array_filter($results, function($r) { return $r['type'] === 'invalid'; }));
                            ?>
                            <small class="text-muted">
                                <strong>Summary:</strong> 
                                <?php echo $validCount; ?> valid entries, 
                                <?php echo $foundCount; ?> with team mappings
                                <?php if ($invalidCount > 0): ?>, <?php echo $invalidCount; ?> invalid<?php endif; ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                CIDR and ranges may show multiple overlapping teams
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>