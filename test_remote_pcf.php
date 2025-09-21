<?php
/**
 * Remote PCF Integration Test Script
 * 
 * This script tests remote PCF connections and provides detailed diagnostics
 */

require_once 'config/database.php';
require_once 'config/pcf_remote_config.php';
require_once 'includes/pcf_remote_functions.php';

$testType = $_GET['test'] ?? 'all';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote PCF Integration Test - AMT</title>
    <link href="scripts/bootstrap.min.css" rel="stylesheet">
    <link href="scripts/all.min.css" rel="stylesheet">
    <style>
        .test-section {
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .test-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .test-body {
            padding: 20px;
        }
        .test-result {
            padding: 10px 15px;
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
        .test-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .config-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .performance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .metric-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .metric-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-vial"></i> Remote PCF Integration Test</h1>
        <p class="text-muted">Comprehensive testing and diagnostics for remote PCF connections</p>

        <div class="mb-3">
            <a href="?test=all" class="btn btn-primary <?php echo $testType === 'all' ? 'active' : ''; ?>">All Tests</a>
            <a href="?test=config" class="btn btn-outline-primary <?php echo $testType === 'config' ? 'active' : ''; ?>">Configuration</a>
            <a href="?test=connection" class="btn btn-outline-primary <?php echo $testType === 'connection' ? 'active' : ''; ?>">Connection</a>
            <a href="?test=data" class="btn btn-outline-primary <?php echo $testType === 'data' ? 'active' : ''; ?>">Data Test</a>
            <a href="?test=performance" class="btn btn-outline-primary <?php echo $testType === 'performance' ? 'active' : ''; ?>">Performance</a>
        </div>

        <?php if ($testType === 'all' || $testType === 'config'): ?>
        <!-- Configuration Test -->
        <div class="test-section">
            <div class="test-header">
                <h5><i class="fas fa-cog"></i> Configuration Test</h5>
            </div>
            <div class="test-body">
                <div class="config-display">
                    <strong>Connection Type:</strong> <?php echo PCF_CONNECTION_TYPE; ?><br>
                    <strong>Cache Enabled:</strong> <?php echo PCF_CACHE_ENABLED ? 'Yes' : 'No'; ?><br>
                    <strong>Cache Duration:</strong> <?php echo PCF_CACHE_DURATION; ?> seconds<br>
                    <strong>Batch Size:</strong> <?php echo PCF_SYNC_BATCH_SIZE; ?><br>
                    <strong>Max Retries:</strong> <?php echo PCF_SYNC_MAX_RETRIES; ?><br>
                    <strong>Connection Timeout:</strong> <?php echo PCF_CONNECTION_TIMEOUT; ?> seconds<br>
                    <strong>Query Timeout:</strong> <?php echo PCF_QUERY_TIMEOUT; ?> seconds<br>
                    <strong>SSL Enabled:</strong> <?php echo PCF_USE_SSL ? 'Yes' : 'No'; ?><br>
                    <strong>SSL Verification:</strong> <?php echo PCF_VERIFY_SSL ? 'Yes' : 'No'; ?>
                </div>

                <?php
                $connectionType = PCF_CONNECTION_TYPE;
                echo "<div class='test-result test-info'>";
                echo "<h6>Connection-Specific Configuration:</h6>";
                
                switch ($connectionType) {
                    case 'local_sqlite':
                        echo "<strong>SQLite Path:</strong> " . PCF_SQLITE_PATH . "<br>";
                        echo "<strong>File Exists:</strong> " . (file_exists(PCF_SQLITE_PATH) ? 'Yes' : 'No') . "<br>";
                        if (file_exists(PCF_SQLITE_PATH)) {
                            echo "<strong>File Size:</strong> " . number_format(filesize(PCF_SQLITE_PATH)) . " bytes<br>";
                            echo "<strong>Last Modified:</strong> " . date('Y-m-d H:i:s', filemtime(PCF_SQLITE_PATH));
                        }
                        break;
                    case 'remote_mysql':
                        echo "<strong>Host:</strong> " . PCF_MYSQL_HOST . "<br>";
                        echo "<strong>Port:</strong> " . PCF_MYSQL_PORT . "<br>";
                        echo "<strong>Database:</strong> " . PCF_MYSQL_DATABASE . "<br>";
                        echo "<strong>Username:</strong> " . PCF_MYSQL_USERNAME;
                        break;
                    case 'remote_postgresql':
                        echo "<strong>Host:</strong> " . PCF_POSTGRESQL_HOST . "<br>";
                        echo "<strong>Port:</strong> " . PCF_POSTGRESQL_PORT . "<br>";
                        echo "<strong>Database:</strong> " . PCF_POSTGRESQL_DATABASE . "<br>";
                        echo "<strong>Username:</strong> " . PCF_POSTGRESQL_USERNAME;
                        break;
                    case 'remote_api':
                        echo "<strong>Base URL:</strong> " . PCF_API_BASE_URL . "<br>";
                        echo "<strong>Has Token:</strong> " . (!empty(PCF_API_TOKEN) ? 'Yes' : 'No') . "<br>";
                        echo "<strong>Has Basic Auth:</strong> " . (!empty(PCF_API_USERNAME) ? 'Yes' : 'No');
                        break;
                }
                echo "</div>";
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($testType === 'all' || $testType === 'connection'): ?>
        <!-- Connection Test -->
        <div class="test-section">
            <div class="test-header">
                <h5><i class="fas fa-plug"></i> Connection Test</h5>
            </div>
            <div class="test-body">
                <?php
                $startTime = microtime(true);
                $testResults = testRemotePcfConnection();
                $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
                
                echo "<div class='test-result " . ($testResults['connection_success'] ? 'test-success' : 'test-error') . "'>";
                echo "<h6><i class='fas fa-" . ($testResults['connection_success'] ? 'check-circle' : 'times-circle') . "'></i> Connection Status</h6>";
                echo "<strong>Result:</strong> " . ($testResults['connection_success'] ? 'Success' : 'Failed') . "<br>";
                echo "<strong>Connection Time:</strong> {$connectionTime} ms<br>";
                echo "<strong>Type:</strong> " . ucfirst(str_replace('_', ' ', $testResults['connection_type']));
                if ($testResults['error']) {
                    echo "<br><strong>Error:</strong> " . htmlspecialchars($testResults['error']);
                }
                echo "</div>";

                if ($testResults['connection_success']) {
                    echo "<div class='test-result test-success'>";
                    echo "<h6><i class='fas fa-database'></i> Data Summary</h6>";
                    echo "<strong>Issues Found:</strong> " . number_format($testResults['issue_count']) . "<br>";
                    echo "<strong>Projects Found:</strong> " . number_format($testResults['project_count']) . "<br>";
                    echo "<strong>Data Available:</strong> " . ($testResults['data_available'] ? 'Yes' : 'No');
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($testType === 'all' || $testType === 'data'): ?>
        <!-- Data Test -->
        <div class="test-section">
            <div class="test-header">
                <h5><i class="fas fa-database"></i> Data Structure Test</h5>
            </div>
            <div class="test-body">
                <?php
                try {
                    $connectionType = PCF_CONNECTION_TYPE;
                    
                    if ($connectionType === 'remote_api') {
                        // Test API endpoints
                        echo "<h6>API Endpoints Test:</h6>";
                        
                        $endpoints = ['issues', 'projects'];
                        foreach ($endpoints as $endpoint) {
                            $startTime = microtime(true);
                            $data = getPcfApiData($endpoint, ['limit' => 1]);
                            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                            
                            if ($data !== null) {
                                echo "<div class='test-result test-success'>";
                                echo "<strong>/{$endpoint}</strong> - Success ({$responseTime} ms)<br>";
                                if (!empty($data) && is_array($data)) {
                                    echo "Sample fields: " . implode(', ', array_keys($data[0]));
                                }
                                echo "</div>";
                            } else {
                                echo "<div class='test-result test-error'>";
                                echo "<strong>/{$endpoint}</strong> - Failed ({$responseTime} ms)";
                                echo "</div>";
                            }
                        }
                    } else {
                        // Test database structure
                        $pcfPdo = getPcfRemoteConnection();
                        
                        if ($pcfPdo) {
                            echo "<h6>Database Structure Test:</h6>";
                            
                            $tableMapping = $GLOBALS['PCF_TABLE_MAPPING'];
                            
                            foreach ($tableMapping as $logicalName => $tableName) {
                                try {
                                    $stmt = $pcfPdo->query("SELECT * FROM {$tableName} LIMIT 1");
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    echo "<div class='test-result test-success'>";
                                    echo "<strong>{$tableName}</strong> - Accessible<br>";
                                    if ($row) {
                                        echo "Fields: " . implode(', ', array_keys($row));
                                    }
                                    echo "</div>";
                                } catch (Exception $e) {
                                    echo "<div class='test-result test-error'>";
                                    echo "<strong>{$tableName}</strong> - Error: " . htmlspecialchars($e->getMessage());
                                    echo "</div>";
                                }
                            }
                        } else {
                            echo "<div class='test-result test-error'>";
                            echo "Cannot connect to database for structure test";
                            echo "</div>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<div class='test-result test-error'>";
                    echo "Data test error: " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($testType === 'all' || $testType === 'performance'): ?>
        <!-- Performance Test -->
        <div class="test-section">
            <div class="test-header">
                <h5><i class="fas fa-tachometer-alt"></i> Performance Test</h5>
            </div>
            <div class="test-body">
                <?php
                try {
                    echo "<p><i class='fas fa-spinner fa-spin'></i> Running performance tests...</p>";
                    flush();
                    
                    $performanceResults = [];
                    
                    // Test connection time
                    $startTime = microtime(true);
                    $testResults = testRemotePcfConnection();
                    $performanceResults['connection_time'] = round((microtime(true) - $startTime) * 1000, 2);
                    
                    if ($testResults['connection_success']) {
                        // Test small data fetch
                        $startTime = microtime(true);
                        $memoryBefore = memory_get_usage();
                        
                        if (PCF_CONNECTION_TYPE === 'remote_api') {
                            $smallData = getPcfApiData('issues', ['limit' => 10]);
                        } else {
                            $pcfPdo = getPcfRemoteConnection();
                            if ($pcfPdo) {
                                $tableMapping = $GLOBALS['PCF_TABLE_MAPPING'];
                                $stmt = $pcfPdo->query("SELECT * FROM {$tableMapping['issues']} LIMIT 10");
                                $smallData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            }
                        }
                        
                        $performanceResults['small_fetch_time'] = round((microtime(true) - $startTime) * 1000, 2);
                        $performanceResults['small_fetch_memory'] = memory_get_usage() - $memoryBefore;
                        
                        // Test larger data fetch
                        $startTime = microtime(true);
                        $memoryBefore = memory_get_usage();
                        
                        if (PCF_CONNECTION_TYPE === 'remote_api') {
                            $largeData = getPcfApiData('issues', ['limit' => 100]);
                        } else {
                            if ($pcfPdo) {
                                $stmt = $pcfPdo->query("SELECT * FROM {$tableMapping['issues']} LIMIT 100");
                                $largeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            }
                        }
                        
                        $performanceResults['large_fetch_time'] = round((microtime(true) - $startTime) * 1000, 2);
                        $performanceResults['large_fetch_memory'] = memory_get_usage() - $memoryBefore;
                        
                        // Test sync performance (dry run)
                        $startTime = microtime(true);
                        $memoryBefore = memory_get_peak_usage();
                        
                        // Simulate sync without actually inserting data
                        if (PCF_CONNECTION_TYPE === 'remote_api') {
                            $allData = getPcfApiData('issues');
                        } else {
                            if ($pcfPdo) {
                                $stmt = $pcfPdo->query("SELECT * FROM {$tableMapping['issues']}");
                                $allData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            }
                        }
                        
                        $performanceResults['full_fetch_time'] = round((microtime(true) - $startTime) * 1000, 2);
                        $performanceResults['full_fetch_memory'] = memory_get_peak_usage() - $memoryBefore;
                        $performanceResults['total_records'] = is_array($allData) ? count($allData) : 0;
                    }
                    
                    // Display results
                    echo "<div class='performance-metrics'>";
                    
                    echo "<div class='metric-card'>";
                    echo "<div class='metric-value'>{$performanceResults['connection_time']} ms</div>";
                    echo "<div>Connection Time</div>";
                    echo "</div>";
                    
                    if (isset($performanceResults['small_fetch_time'])) {
                        echo "<div class='metric-card'>";
                        echo "<div class='metric-value'>{$performanceResults['small_fetch_time']} ms</div>";
                        echo "<div>Small Fetch (10 records)</div>";
                        echo "</div>";
                        
                        echo "<div class='metric-card'>";
                        echo "<div class='metric-value'>{$performanceResults['large_fetch_time']} ms</div>";
                        echo "<div>Large Fetch (100 records)</div>";
                        echo "</div>";
                        
                        echo "<div class='metric-card'>";
                        echo "<div class='metric-value'>{$performanceResults['full_fetch_time']} ms</div>";
                        echo "<div>Full Fetch ({$performanceResults['total_records']} records)</div>";
                        echo "</div>";
                        
                        echo "<div class='metric-card'>";
                        echo "<div class='metric-value'>" . round($performanceResults['full_fetch_memory'] / 1024 / 1024, 2) . " MB</div>";
                        echo "<div>Memory Usage</div>";
                        echo "</div>";
                        
                        // Calculate throughput
                        if ($performanceResults['full_fetch_time'] > 0 && $performanceResults['total_records'] > 0) {
                            $throughput = round($performanceResults['total_records'] / ($performanceResults['full_fetch_time'] / 1000), 2);
                            echo "<div class='metric-card'>";
                            echo "<div class='metric-value'>{$throughput}</div>";
                            echo "<div>Records/Second</div>";
                            echo "</div>";
                        }
                    }
                    
                    echo "</div>";
                    
                    // Performance recommendations
                    echo "<div class='test-result test-info'>";
                    echo "<h6><i class='fas fa-lightbulb'></i> Performance Recommendations</h6>";
                    
                    if ($performanceResults['connection_time'] > 5000) {
                        echo "• Connection time is high (>{$performanceResults['connection_time']}ms). Consider optimizing network or using caching.<br>";
                    }
                    
                    if (isset($performanceResults['full_fetch_memory']) && $performanceResults['full_fetch_memory'] > 50 * 1024 * 1024) {
                        echo "• Memory usage is high (>" . round($performanceResults['full_fetch_memory'] / 1024 / 1024, 2) . "MB). Consider implementing batch processing.<br>";
                    }
                    
                    if (isset($performanceResults['total_records']) && $performanceResults['total_records'] > 10000) {
                        echo "• Large dataset detected ({$performanceResults['total_records']} records). Enable caching and consider batch sync.<br>";
                    }
                    
                    echo "• Current batch size: " . PCF_SYNC_BATCH_SIZE . " records<br>";
                    echo "• Cache is " . (PCF_CACHE_ENABLED ? 'enabled' : 'disabled');
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='test-result test-error'>";
                    echo "Performance test error: " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="setup_remote_pcf.php" class="btn btn-primary">
                <i class="fas fa-cog"></i> Setup Remote PCF
            </a>
            <?php if ($testType === 'all' || $testType === 'connection'): ?>
                <?php if (isset($testResults) && $testResults['connection_success'] && $testResults['data_available']): ?>
                    <a href="pcf_dashboard.php" class="btn btn-success">
                        <i class="fas fa-tachometer-alt"></i> View PCF Dashboard
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="scripts/bootstrap.bundle.min.js"></script>
</body>
</html>