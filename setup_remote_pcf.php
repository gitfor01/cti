<?php
/**
 * Remote PCF Integration Setup Script
 * 
 * This script helps configure and test remote PCF database connections
 */

require_once 'config/database.php';
require_once 'config/pcf_remote_config.php';
require_once 'includes/pcf_remote_functions.php';
require_once 'includes/pcf_functions.php'; // For createPcfFindingsTable function

$step = $_GET['step'] ?? 'config';
$message = '';
$messageType = '';
$testConfig = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_connection'])) {
        $step = 'test';
        // Capture form data for testing
        $testConfig = [
            'connection_type' => $_POST['connection_type'] ?? PCF_CONNECTION_TYPE,
            'sqlite_path' => $_POST['sqlite_path'] ?? PCF_SQLITE_PATH,
            'remote_sqlite_host' => $_POST['remote_sqlite_host'] ?? (defined('PCF_REMOTE_SQLITE_HOST') ? PCF_REMOTE_SQLITE_HOST : ''),
            'remote_sqlite_method' => $_POST['remote_sqlite_method'] ?? (defined('PCF_REMOTE_SQLITE_METHOD') ? PCF_REMOTE_SQLITE_METHOD : 'ssh'),
            'remote_sqlite_username' => $_POST['remote_sqlite_username'] ?? (defined('PCF_REMOTE_SQLITE_USERNAME') ? PCF_REMOTE_SQLITE_USERNAME : ''),
            'remote_sqlite_password' => $_POST['remote_sqlite_password'] ?? (defined('PCF_REMOTE_SQLITE_PASSWORD') ? PCF_REMOTE_SQLITE_PASSWORD : ''),
            'remote_sqlite_path' => $_POST['remote_sqlite_path'] ?? (defined('PCF_REMOTE_SQLITE_PATH') ? PCF_REMOTE_SQLITE_PATH : ''),
            'remote_sqlite_port' => $_POST['remote_sqlite_port'] ?? (defined('PCF_REMOTE_SQLITE_PORT') ? PCF_REMOTE_SQLITE_PORT : 22),
            'remote_sqlite_cache' => $_POST['remote_sqlite_cache'] ?? (defined('PCF_REMOTE_SQLITE_LOCAL_CACHE') ? PCF_REMOTE_SQLITE_LOCAL_CACHE : ''),
            'mysql_host' => $_POST['mysql_host'] ?? (defined('PCF_MYSQL_HOST') ? PCF_MYSQL_HOST : ''),
            'mysql_port' => $_POST['mysql_port'] ?? (defined('PCF_MYSQL_PORT') ? PCF_MYSQL_PORT : 3306),
            'mysql_database' => $_POST['mysql_database'] ?? (defined('PCF_MYSQL_DATABASE') ? PCF_MYSQL_DATABASE : ''),
            'mysql_username' => $_POST['mysql_username'] ?? (defined('PCF_MYSQL_USERNAME') ? PCF_MYSQL_USERNAME : ''),
            'mysql_password' => $_POST['mysql_password'] ?? (defined('PCF_MYSQL_PASSWORD') ? PCF_MYSQL_PASSWORD : ''),
            'postgresql_host' => $_POST['postgresql_host'] ?? (defined('PCF_POSTGRESQL_HOST') ? PCF_POSTGRESQL_HOST : ''),
            'postgresql_port' => $_POST['postgresql_port'] ?? (defined('PCF_POSTGRESQL_PORT') ? PCF_POSTGRESQL_PORT : 5432),
            'postgresql_database' => $_POST['postgresql_database'] ?? (defined('PCF_POSTGRESQL_DATABASE') ? PCF_POSTGRESQL_DATABASE : ''),
            'postgresql_username' => $_POST['postgresql_username'] ?? (defined('PCF_POSTGRESQL_USERNAME') ? PCF_POSTGRESQL_USERNAME : ''),
            'postgresql_password' => $_POST['postgresql_password'] ?? (defined('PCF_POSTGRESQL_PASSWORD') ? PCF_POSTGRESQL_PASSWORD : ''),
            'api_base_url' => $_POST['api_base_url'] ?? (defined('PCF_API_BASE_URL') ? PCF_API_BASE_URL : ''),
            'api_token' => $_POST['api_token'] ?? (defined('PCF_API_TOKEN') ? PCF_API_TOKEN : ''),
            'api_username' => $_POST['api_username'] ?? (defined('PCF_API_USERNAME') ? PCF_API_USERNAME : ''),
            'api_password' => $_POST['api_password'] ?? (defined('PCF_API_PASSWORD') ? PCF_API_PASSWORD : ''),
        ];
    } elseif (isset($_POST['sync_data'])) {
        $step = 'sync';
    } elseif (isset($_POST['save_config'])) {
        $step = 'save_config';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote PCF Integration Setup - AMT</title>
    <link href="scripts/bootstrap.min.css" rel="stylesheet">
    <link href="scripts/all.min.css" rel="stylesheet">
    <style>
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            position: relative;
        }
        .step.active {
            background: #007bff;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .config-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .test-result {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .test-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .test-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .test-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-network-wired"></i> Remote PCF Integration Setup</h1>
        <p class="text-muted">Configure and test remote PCF database connections</p>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $step === 'config' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i><br>Configuration
            </div>
            <div class="step <?php echo $step === 'test' ? 'active' : ''; ?>">
                <i class="fas fa-plug"></i><br>Test Connection
            </div>
            <div class="step <?php echo $step === 'sync' ? 'active' : ''; ?>">
                <i class="fas fa-sync"></i><br>Sync Data
            </div>
            <div class="step <?php echo $step === 'complete' ? 'active' : ''; ?>">
                <i class="fas fa-check"></i><br>Complete
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($step === 'config'): ?>
            <!-- Configuration Step -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-cog"></i> PCF Connection Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="config-section">
                            <h6><i class="fas fa-database"></i> Connection Type</h6>
                            <p class="text-muted">Select how you want to connect to your PCF instance</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="connection_type" id="local_sqlite" value="local_sqlite" <?php echo PCF_CONNECTION_TYPE === 'local_sqlite' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="local_sqlite">
                                            <strong>Local SQLite</strong><br>
                                            <small class="text-muted">PCF database file on same server</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="connection_type" id="remote_sqlite" value="remote_sqlite" <?php echo PCF_CONNECTION_TYPE === 'remote_sqlite' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="remote_sqlite">
                                            <strong>Remote SQLite</strong><br>
                                            <small class="text-muted">SQLite file on remote server</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="connection_type" id="remote_mysql" value="remote_mysql" <?php echo PCF_CONNECTION_TYPE === 'remote_mysql' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="remote_mysql">
                                            <strong>Remote MySQL</strong><br>
                                            <small class="text-muted">PCF data in MySQL database</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="connection_type" id="remote_postgresql" value="remote_postgresql" <?php echo PCF_CONNECTION_TYPE === 'remote_postgresql' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="remote_postgresql">
                                            <strong>Remote PostgreSQL</strong><br>
                                            <small class="text-muted">PCF data in PostgreSQL database</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="connection_type" id="remote_api" value="remote_api" <?php echo PCF_CONNECTION_TYPE === 'remote_api' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="remote_api">
                                            <strong>Remote API</strong><br>
                                            <small class="text-muted">PCF with REST API access</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Local SQLite Configuration -->
                        <div class="config-section" id="sqlite_config" style="display: <?php echo PCF_CONNECTION_TYPE === 'local_sqlite' ? 'block' : 'none'; ?>;">
                            <h6><i class="fas fa-file-database"></i> SQLite Configuration</h6>
                            <div class="mb-3">
                                <label for="sqlite_path" class="form-label">Database File Path</label>
                                <input type="text" class="form-control" id="sqlite_path" name="sqlite_path" value="<?php echo PCF_SQLITE_PATH; ?>" placeholder="/path/to/pcf/database.sqlite3">
                                <div class="form-text">Full path to the PCF SQLite database file</div>
                            </div>
                        </div>

                        <!-- Remote SQLite Configuration -->
                        <div class="config-section" id="remote_sqlite_config" style="display: <?php echo PCF_CONNECTION_TYPE === 'remote_sqlite' ? 'block' : 'none'; ?>;">
                            <h6><i class="fas fa-cloud-download-alt"></i> Remote SQLite Configuration</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="remote_sqlite_host" class="form-label">Remote Host</label>
                                        <input type="text" class="form-control" id="remote_sqlite_host" name="remote_sqlite_host" value="<?php echo defined('PCF_REMOTE_SQLITE_HOST') ? PCF_REMOTE_SQLITE_HOST : ''; ?>" placeholder="pcf-server.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="remote_sqlite_method" class="form-label">Access Method</label>
                                        <select class="form-control" id="remote_sqlite_method" name="remote_sqlite_method">
                                            <option value="ssh" <?php echo (defined('PCF_REMOTE_SQLITE_METHOD') && PCF_REMOTE_SQLITE_METHOD === 'ssh') ? 'selected' : ''; ?>>SSH/SCP</option>
                                            <option value="http" <?php echo (defined('PCF_REMOTE_SQLITE_METHOD') && PCF_REMOTE_SQLITE_METHOD === 'http') ? 'selected' : ''; ?>>HTTP</option>
                                            <option value="https" <?php echo (defined('PCF_REMOTE_SQLITE_METHOD') && PCF_REMOTE_SQLITE_METHOD === 'https') ? 'selected' : ''; ?>>HTTPS</option>
                                            <option value="ftp" <?php echo (defined('PCF_REMOTE_SQLITE_METHOD') && PCF_REMOTE_SQLITE_METHOD === 'ftp') ? 'selected' : ''; ?>>FTP</option>
                                            <option value="smb" <?php echo (defined('PCF_REMOTE_SQLITE_METHOD') && PCF_REMOTE_SQLITE_METHOD === 'smb') ? 'selected' : ''; ?>>SMB</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="remote_sqlite_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="remote_sqlite_username" name="remote_sqlite_username" value="<?php echo defined('PCF_REMOTE_SQLITE_USERNAME') ? PCF_REMOTE_SQLITE_USERNAME : ''; ?>" placeholder="remote_user">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="remote_sqlite_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="remote_sqlite_password" name="remote_sqlite_password" value="<?php echo defined('PCF_REMOTE_SQLITE_PASSWORD') ? PCF_REMOTE_SQLITE_PASSWORD : ''; ?>" placeholder="password">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="remote_sqlite_path" class="form-label">Remote File Path</label>
                                        <input type="text" class="form-control" id="remote_sqlite_path" name="remote_sqlite_path" value="<?php echo defined('PCF_REMOTE_SQLITE_PATH') ? PCF_REMOTE_SQLITE_PATH : ''; ?>" placeholder="/path/to/remote/pcf/database.sqlite3">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="remote_sqlite_port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="remote_sqlite_port" name="remote_sqlite_port" value="<?php echo defined('PCF_REMOTE_SQLITE_PORT') ? PCF_REMOTE_SQLITE_PORT : '22'; ?>" placeholder="22">
                                        <div class="form-text">SSH: 22, FTP: 21, HTTP: 80, HTTPS: 443</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="remote_sqlite_cache" class="form-label">Local Cache Path</label>
                                <input type="text" class="form-control" id="remote_sqlite_cache" name="remote_sqlite_cache" value="<?php echo defined('PCF_REMOTE_SQLITE_LOCAL_CACHE') ? PCF_REMOTE_SQLITE_LOCAL_CACHE : '/tmp/pcf_remote_cache.sqlite3'; ?>" placeholder="/tmp/pcf_remote_cache.sqlite3">
                                <div class="form-text">Local path where remote SQLite file will be cached</div>
                            </div>
                        </div>

                        <!-- MySQL Configuration -->
                        <div class="config-section" id="mysql_config" style="display: <?php echo PCF_CONNECTION_TYPE === 'remote_mysql' ? 'block' : 'none'; ?>;">
                            <h6><i class="fas fa-server"></i> MySQL Configuration</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mysql_host" class="form-label">Host</label>
                                        <input type="text" class="form-control" id="mysql_host" name="mysql_host" value="<?php echo PCF_MYSQL_HOST; ?>" placeholder="pcf-server.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mysql_port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="mysql_port" name="mysql_port" value="<?php echo PCF_MYSQL_PORT; ?>" placeholder="3306">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="mysql_database" class="form-label">Database</label>
                                        <input type="text" class="form-control" id="mysql_database" name="mysql_database" value="<?php echo PCF_MYSQL_DATABASE; ?>" placeholder="pcf_db">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="mysql_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="mysql_username" name="mysql_username" value="<?php echo PCF_MYSQL_USERNAME; ?>" placeholder="pcf_user">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="mysql_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="mysql_password" name="mysql_password" value="<?php echo PCF_MYSQL_PASSWORD; ?>" placeholder="password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PostgreSQL Configuration -->
                        <div class="config-section" id="postgresql_config" style="display: <?php echo PCF_CONNECTION_TYPE === 'remote_postgresql' ? 'block' : 'none'; ?>;">
                            <h6><i class="fas fa-elephant"></i> PostgreSQL Configuration</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="postgresql_host" class="form-label">Host</label>
                                        <input type="text" class="form-control" id="postgresql_host" name="postgresql_host" value="<?php echo PCF_POSTGRESQL_HOST; ?>" placeholder="pcf-server.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="postgresql_port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="postgresql_port" name="postgresql_port" value="<?php echo PCF_POSTGRESQL_PORT; ?>" placeholder="5432">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="postgresql_database" class="form-label">Database</label>
                                        <input type="text" class="form-control" id="postgresql_database" name="postgresql_database" value="<?php echo PCF_POSTGRESQL_DATABASE; ?>" placeholder="pcf_db">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="postgresql_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="postgresql_username" name="postgresql_username" value="<?php echo PCF_POSTGRESQL_USERNAME; ?>" placeholder="pcf_user">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="postgresql_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="postgresql_password" name="postgresql_password" value="<?php echo PCF_POSTGRESQL_PASSWORD; ?>" placeholder="password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- API Configuration -->
                        <div class="config-section" id="api_config" style="display: <?php echo PCF_CONNECTION_TYPE === 'remote_api' ? 'block' : 'none'; ?>;">
                            <h6><i class="fas fa-cloud"></i> API Configuration</h6>
                            <div class="mb-3">
                                <label for="api_base_url" class="form-label">Base URL</label>
                                <input type="url" class="form-control" id="api_base_url" name="api_base_url" value="<?php echo PCF_API_BASE_URL; ?>" placeholder="https://pcf-server.com/api">
                                <div class="form-text">Base URL for the PCF REST API</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_token" class="form-label">API Token</label>
                                        <input type="text" class="form-control" id="api_token" name="api_token" value="<?php echo PCF_API_TOKEN; ?>" placeholder="Bearer token (optional)">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="api_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="api_username" name="api_username" value="<?php echo PCF_API_USERNAME; ?>" placeholder="Basic auth username">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="api_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="api_password" name="api_password" value="<?php echo PCF_API_PASSWORD; ?>" placeholder="Basic auth password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" name="test_connection" class="btn btn-primary">
                                <i class="fas fa-plug"></i> Test Connection
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($step === 'test'): ?>
            <!-- Test Connection Step -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-plug"></i> Connection Test Results</h5>
                </div>
                <div class="card-body">
                    <?php
                    $testResults = testRemotePcfConnection($testConfig);
                    ?>
                    
                    <div class="test-result <?php echo $testResults['connection_success'] ? 'test-success' : 'test-error'; ?>">
                        <h6>
                            <i class="fas fa-<?php echo $testResults['connection_success'] ? 'check-circle' : 'times-circle'; ?>"></i>
                            Connection Test
                        </h6>
                        <p>
                            <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $testResults['connection_type'])); ?><br>
                            <strong>Status:</strong> <?php echo $testResults['connection_success'] ? 'Success' : 'Failed'; ?>
                            <?php if ($testResults['error']): ?>
                                <br><strong>Error:</strong> <?php echo htmlspecialchars($testResults['error']); ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($testResults['connection_success']): ?>
                        <div class="test-result <?php echo $testResults['data_available'] ? 'test-success' : 'test-warning'; ?>">
                            <h6>
                                <i class="fas fa-<?php echo $testResults['data_available'] ? 'database' : 'exclamation-triangle'; ?>"></i>
                                Data Availability
                            </h6>
                            <p>
                                <strong>Issues Found:</strong> <?php echo number_format($testResults['issue_count']); ?><br>
                                <strong>Projects Found:</strong> <?php echo number_format($testResults['project_count']); ?>
                            </p>
                            <?php if (!$testResults['data_available']): ?>
                                <p class="text-warning">
                                    <i class="fas fa-info-circle"></i>
                                    No issues found in the PCF database. Make sure PCF contains data before syncing.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="?step=config" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Configuration
                            </a>
                            <?php if ($testResults['data_available']): ?>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="sync_data" class="btn btn-success">
                                        <i class="fas fa-sync"></i> Sync Data (<?php echo number_format($testResults['issue_count']); ?> issues)
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-warning" disabled>
                                    <i class="fas fa-exclamation-triangle"></i> No Data to Sync
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between mt-4">
                            <a href="?step=config" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Configuration
                            </a>
                            <button class="btn btn-danger" disabled>
                                <i class="fas fa-times"></i> Fix Connection Issues First
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($step === 'sync'): ?>
            <!-- Sync Data Step -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-sync"></i> Data Synchronization</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        echo "<p><i class='fas fa-spinner fa-spin'></i> Starting synchronization...</p>";
                        flush();
                        
                        $syncResult = syncRemotePcfFindings($pdo);
                        
                        if ($syncResult['success']) {
                            echo "<div class='test-result test-success'>";
                            echo "<h6><i class='fas fa-check-circle'></i> Synchronization Successful</h6>";
                            echo "<p><strong>Findings Synced:</strong> " . number_format($syncResult['count']) . "</p>";
                            echo "<p>" . htmlspecialchars($syncResult['message']) . "</p>";
                            echo "</div>";
                            
                            $step = 'complete';
                        } else {
                            echo "<div class='test-result test-error'>";
                            echo "<h6><i class='fas fa-times-circle'></i> Synchronization Failed</h6>";
                            echo "<p><strong>Error:</strong> " . htmlspecialchars($syncResult['error']) . "</p>";
                            echo "</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='test-result test-error'>";
                        echo "<h6><i class='fas fa-times-circle'></i> Synchronization Error</h6>";
                        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "</div>";
                    }
                    ?>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="?step=test" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Test
                        </a>
                        <?php if ($step === 'complete'): ?>
                            <a href="pcf_dashboard.php" class="btn btn-success">
                                <i class="fas fa-tachometer-alt"></i> View PCF Dashboard
                            </a>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="sync_data" class="btn btn-warning">
                                    <i class="fas fa-redo"></i> Retry Sync
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($step === 'complete'): ?>
            <!-- Complete Step -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-check"></i> Setup Complete</h5>
                </div>
                <div class="card-body">
                    <div class="test-result test-success">
                        <h6><i class="fas fa-party-horn"></i> Remote PCF Integration Ready!</h6>
                        <p>Your remote PCF integration has been successfully configured and data has been synchronized.</p>
                    </div>

                    <h6>What's Next:</h6>
                    <ul>
                        <li><a href="pcf_dashboard.php">Access the PCF Dashboard</a> to view your synchronized findings</li>
                        <li>Set up automatic synchronization using the cron script</li>
                        <li>Configure filters and explore the detailed finding views</li>
                        <li>Monitor sync logs for any issues</li>
                    </ul>

                    <h6>Automatic Sync Setup:</h6>
                    <p>To enable automatic hourly synchronization, add this to your crontab:</p>
                    <pre class="bg-light p-3"><code>0 * * * * /usr/bin/php <?php echo __DIR__; ?>/cron_remote_pcf_sync.php</code></pre>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Back to Dashboard
                        </a>
                        <a href="pcf_dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> View PCF Dashboard
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="scripts/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide configuration sections based on connection type
        document.querySelectorAll('input[name="connection_type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                // Hide all config sections
                document.querySelectorAll('.config-section[id$="_config"]').forEach(function(section) {
                    section.style.display = 'none';
                });
                
                // Show selected config section
                const selectedConfig = this.value + '_config';
                const configSection = document.getElementById(selectedConfig);
                if (configSection) {
                    configSection.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>