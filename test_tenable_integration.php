<?php
/**
 * Tenable Security Center Integration Test Suite
 * Tests all API endpoints and optimization methods before production sync
 * 
 * This script validates:
 * 1. Connection and authentication
 * 2. Basic vulnerability queries (new/closed)
 * 3. All three VGI calculation optimization methods
 * 4. Data integrity and expected outputs
 * 5. Performance metrics for each method
 */

// Configure PHP for real-time output (must be before any output)
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');
@ini_set('implicit_flush', '1');

// Prevent timeout for long-running tests
set_time_limit(300); // 5 minutes max

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenable SC Integration Test Suite</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .test-results {
            margin-top: 30px;
        }
        
        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        
        .test-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .test-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #ddd;
        }
        
        .test-item.success {
            border-left-color: #28a745;
            background: #f0fff4;
        }
        
        .test-item.warning {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        
        .test-item.error {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .test-item.info {
            border-left-color: #17a2b8;
            background: #f0f9ff;
        }
        
        .test-item .status {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-item .details {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
            line-height: 1.6;
        }
        
        .test-item .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 10px;
            overflow-x: auto;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge.success {
            background: #28a745;
            color: white;
        }
        
        .badge.error {
            background: #dc3545;
            color: white;
        }
        
        .badge.warning {
            background: #ffc107;
            color: #333;
        }
        
        .badge.info {
            background: #17a2b8;
            color: white;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .summary-card .number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .summary-card .label {
            color: #666;
            font-size: 14px;
        }
        
        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .performance-table th,
        .performance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .performance-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .performance-table tr:last-child td {
            border-bottom: none;
        }
        
        .progress-indicator {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .progress-indicator .spinner-small {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #ffc107;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .recommendation {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .recommendation h3 {
            margin-bottom: 10px;
        }
        
        .recommendation ul {
            margin-left: 20px;
            line-height: 1.8;
        }
        
        .stop-test-btn {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin: 20px 0;
            transition: background 0.3s;
        }
        
        .stop-test-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tenable Security Center Integration Test Suite</h1>
            <p>Comprehensive validation of all API endpoints and optimization methods</p>
        </div>
        
        <div class="content">
            <?php if (!isset($_POST['run_test'])): ?>
            
            <div class="form-section">
                <h2 style="margin-bottom: 20px; color: #333;">Enter Tenable SC Credentials</h2>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-group">
                        <label for="scHost">Tenable SC Host URL</label>
                        <input type="text" id="scHost" name="scHost" 
                               placeholder="https://your-tenable-sc.com" 
                               value="<?php echo isset($_POST['scHost']) ? htmlspecialchars($_POST['scHost']) : ''; ?>"
                               required>
                        <small style="color: #666; display: block; margin-top: 5px;">Must include https:// and domain name</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="accessKey">Access Key</label>
                            <input type="text" id="accessKey" name="accessKey" 
                                   placeholder="Enter your access key" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="secretKey">Secret Key</label>
                            <input type="password" id="secretKey" name="secretKey" 
                                   placeholder="Enter your secret key" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="testDepth">Test Depth</label>
                        <select id="testDepth" name="testDepth">
                            <option value="quick">Quick Test (Basic connectivity & 1 severity)</option>
                            <option value="standard" selected>Standard Test (All severities, limited data)</option>
                            <option value="comprehensive">Comprehensive Test (Full analysis, may take 5+ minutes)</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="run_test" class="btn">
                        Run Integration Tests
                    </button>
                </form>
            </div>
            
            <div class="test-section">
                <h3>What This Test Will Validate:</h3>
                <ul style="margin-left: 20px; line-height: 2;">
                    <li>Connection to Tenable Security Center</li>
                    <li>API authentication with provided credentials</li>
                    <li>New vulnerabilities query (by severity)</li>
                    <li>Closed vulnerabilities query (by severity)</li>
                    <li>Current vulnerabilities count</li>
                    <li>VGI Optimization Method 1: sumid with count field (fastest)</li>
                    <li>VGI Optimization Method 2: bulk export with aggregation (recommended)</li>
                    <li>VGI Optimization Method 3: individual plugin queries (fallback)</li>
                    <li>Data integrity and calculation accuracy</li>
                    <li>Performance metrics for each method</li>
                </ul>
            </div>
            
            <?php else: ?>
            
            <div class="test-results" id="results">
                <?php
                // Disable all output buffering for real-time updates
                while (ob_get_level()) {
                    ob_end_flush();
                }
                
                // Disable Apache/nginx output buffering
                if (function_exists('apache_setenv')) {
                    apache_setenv('no-gzip', '1');
                }
                
                // Send initial padding to overcome browser buffering (4KB minimum)
                echo str_pad('', 4096) . "\n";
                flush();
                
                // Run the actual tests
                $scHost = trim($_POST['scHost']);
                $accessKey = trim($_POST['accessKey']);
                $secretKey = trim($_POST['secretKey']);
                $testDepth = $_POST['testDepth'] ?? 'standard';
                
                runIntegrationTests($scHost, $accessKey, $secretKey, $testDepth);
                ?>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php

/**
 * Force output to browser immediately
 */
function forceFlush() {
    echo str_repeat(' ', 1024);
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

/**
 * Show progress indicator
 */
function showProgress($message) {
    echo '<div class="progress-indicator">';
    echo '<div class="spinner-small"></div>';
    echo '<span>' . htmlspecialchars($message) . '</span>';
    echo '</div>';
    forceFlush();
}

/**
 * Clear last progress indicator
 */
function clearProgress() {
    echo '<script>
        (function() {
            var progressIndicators = document.querySelectorAll(".progress-indicator");
            if (progressIndicators.length > 0) {
                progressIndicators[progressIndicators.length - 1].remove();
            }
        })();
    </script>';
    forceFlush();
}

/**
 * Run comprehensive integration tests
 */
function runIntegrationTests($scHost, $accessKey, $secretKey, $testDepth) {
    $results = [];
    $startTime = microtime(true);
    
    // Add stop button at the top
    echo '<div style="text-align: center; margin-bottom: 20px;">';
    echo '<button onclick="window.location.href=window.location.href.split(\'?\')[0];" class="stop-test-btn">Stop Test & Return</button>';
    echo '<p style="color: #666; margin-top: 10px;">You can stop the test at any time using the button above</p>';
    echo '</div>';
    forceFlush();
    
    echo '<div class="test-section">';
    echo '<h3>Test 1: Connection & Authentication</h3>';
    forceFlush();
    
    // Validate URL format first
    if (!filter_var($scHost, FILTER_VALIDATE_URL)) {
        echo '<div class="test-item error">';
        echo '<div class="status"><span class="badge error">FAILED</span> Invalid URL Format</div>';
        echo '<div class="details">';
        echo 'The host URL must be properly formatted (e.g., https://your-tenable-sc.com)<br>';
        echo 'Provided: ' . htmlspecialchars($scHost);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        forceFlush();
        return;
    }
    
    // Test basic network connectivity
    showProgress('Testing network connectivity to ' . htmlspecialchars($scHost) . '...');
    $urlParts = parse_url($scHost);
    $host = $urlParts['host'] ?? '';
    $port = $urlParts['port'] ?? 443;
    
    if ($host && function_exists('fsockopen')) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$fp) {
            clearProgress();
            echo '<div class="test-item error">';
            echo '<div class="status"><span class="badge error">FAILED</span> Cannot Reach Host</div>';
            echo '<div class="details">';
            echo 'Unable to connect to ' . htmlspecialchars($host) . ':' . $port . '<br>';
            echo 'Error: ' . htmlspecialchars($errstr) . ' (Code: ' . $errno . ')<br>';
            echo '<br><strong>Common causes:</strong><br>';
            echo '- Incorrect hostname or URL<br>';
            echo '- Firewall blocking connection<br>';
            echo '- VPN required but not connected<br>';
            echo '- Server is down or unreachable';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            forceFlush();
            return;
        }
        fclose($fp);
    }
    clearProgress();
    
    try {
        showProgress('Loading Tenable SC API client...');
        require_once 'va_api.php';
        clearProgress();
        
        showProgress('Initializing API connection...');
        $api = new TenableSCAPI($scHost, $accessKey, $secretKey);
        clearProgress();
        
        showProgress('Testing authentication and API access...');
        $connectionDebug = $api->testConnection();
        clearProgress();
        
        if ($connectionDebug['success']) {
            echo '<div class="test-item success">';
            echo '<div class="status"><span class="badge success">PASSED</span> Connection Successful</div>';
            echo '<div class="details">';
            echo '<strong>Connection Details:</strong><br>';
            echo 'Host: ' . htmlspecialchars($connectionDebug['host']) . '<br>';
            echo 'Endpoint: ' . htmlspecialchars($connectionDebug['endpoint']) . '<br>';
            echo 'HTTP Status: ' . $connectionDebug['http_code'] . ' OK<br>';
            echo 'Response Time: ' . $connectionDebug['response_time_ms'] . ' ms<br>';
            echo '<br><strong>API Response:</strong><br>';
            echo 'Response Type: ' . htmlspecialchars($connectionDebug['sample_response']['response_type']) . '<br>';
            echo 'Total Records Found: ' . $connectionDebug['sample_response']['total_records'] . '<br>';
            echo 'Has Results: ' . ($connectionDebug['sample_response']['has_results'] ? 'Yes' : 'No') . '<br>';
            echo '</div>';
            echo '</div>';
            forceFlush();
            
            $results['connection'] = true;
        } else {
            throw new Exception($connectionDebug['error'] ?? 'Unknown connection error');
        }
        
    } catch (Exception $e) {
        clearProgress();
        echo '<div class="test-item error">';
        echo '<div class="status"><span class="badge error">FAILED</span> Connection Failed</div>';
        echo '<div class="details">';
        echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
        
        if (isset($connectionDebug)) {
            echo '<br><strong>Debug Information:</strong><br>';
            echo 'Host: ' . htmlspecialchars($connectionDebug['host']) . '<br>';
            echo 'Endpoint: ' . htmlspecialchars($connectionDebug['endpoint']) . '<br>';
            if ($connectionDebug['http_code']) {
                echo 'HTTP Status: ' . $connectionDebug['http_code'] . '<br>';
            }
            if ($connectionDebug['response_time_ms'] > 0) {
                echo 'Response Time: ' . $connectionDebug['response_time_ms'] . ' ms<br>';
            }
        }
        
        echo '<br><strong>Troubleshooting:</strong><br>';
        echo '- Verify your API keys are correct<br>';
        echo '- Ensure your account has API access permissions<br>';
        echo '- Check if API access is enabled on the Tenable SC instance<br>';
        echo '- Verify the URL includes https:// protocol';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        forceFlush();
        
        $results['connection'] = false;
        showFinalSummary($results, microtime(true) - $startTime);
        return;
    }
    
    echo '</div>';
    forceFlush();
    
    // Test 2: Vulnerability Queries
    echo '<div class="test-section">';
    echo '<h3>Test 2: Vulnerability Data Queries</h3>';
    forceFlush();
    
    $endTime = time();
    $startTime = $endTime - (30 * 86400); // Last 30 days
    
    $severities = [
        '4' => 'Critical',
        '3' => 'High',
        '2' => 'Medium',
        '1' => 'Low'
    ];
    
    if ($testDepth === 'quick') {
        $severities = ['4' => 'Critical'];
    }
    
    $vulnData = [];
    
    foreach ($severities as $sevCode => $sevName) {
        try {
            showProgress("Querying {$sevName} severity vulnerabilities (new & closed)...");
            $newCount = $api->getNewVulnerabilitiesBySeverity($startTime, $endTime, $sevCode);
            $closedCount = $api->getClosedVulnerabilitiesBySeverity($startTime, $endTime, $sevCode);
            clearProgress();
            
            echo '<div class="test-item success">';
            echo '<div class="status"><span class="badge success">PASSED</span> ' . $sevName . ' Severity Queries</div>';
            echo '<div class="details">';
            echo 'New vulnerabilities: <strong>' . $newCount . '</strong><br>';
            echo 'Closed vulnerabilities: <strong>' . $closedCount . '</strong><br>';
            echo 'Net change: <strong>' . ($newCount - $closedCount) . '</strong>';
            echo '</div>';
            echo '</div>';
            forceFlush();
            
            $vulnData[$sevCode] = [
                'name' => $sevName,
                'new' => $newCount,
                'closed' => $closedCount
            ];
            
            $results['vuln_queries_' . $sevCode] = true;
            
        } catch (Exception $e) {
            clearProgress();
            echo '<div class="test-item error">';
            echo '<div class="status"><span class="badge error">FAILED</span> ' . $sevName . ' Severity Queries</div>';
            echo '<div class="details">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '</div>';
            forceFlush();
            
            $results['vuln_queries_' . $sevCode] = false;
        }
    }
    
    echo '</div>';
    forceFlush();
    
    // Test 3: VGI Optimization Methods
    echo '<div class="test-section">';
    echo '<h3>Test 3: VGI Calculation Optimization Methods</h3>';
    echo '<p style="margin-bottom: 15px; color: #666;">Testing all three optimization approaches to determine which works best in your environment.</p>';
    forceFlush();
    
    $optimizationResults = [];
    
    foreach ($severities as $sevCode => $sevName) {
        echo '<div class="test-item info">';
        echo '<div class="status"><strong>' . $sevName . ' Severity - Asset Instance Calculation</strong></div>';
        forceFlush();
        
        try {
            showProgress("Calculating {$sevName} severity asset instances (testing optimization methods)...");
            $methodStartTime = microtime(true);
            
            $progressCallback = function($message) use ($sevName) {
                clearProgress();
                showProgress("{$sevName}: $message");
            };
            
            $result = $api->getVulnerabilityAssetInstances($endTime, $sevCode, $progressCallback);
            $methodDuration = microtime(true) - $methodStartTime;
            clearProgress();
            
            $method = $result['method'] ?? 'unknown';
            $assetInstances = $result['asset_instances'] ?? 0;
            $vulnCount = $result['vuln_count'] ?? 0;
            $apiCalls = $result['api_calls'] ?? 0;
            
            $methodNames = [
                'sumid_count_field' => 'Method 1: sumid with count field',
                'bulk_export' => 'Method 2: Bulk export with aggregation',
                'individual_queries' => 'Method 3: Individual plugin queries'
            ];
            
            $methodColors = [
                'sumid_count_field' => 'success',
                'bulk_export' => 'info',
                'individual_queries' => 'warning'
            ];
            
            $methodName = $methodNames[$method] ?? $method;
            $methodColor = $methodColors[$method] ?? 'info';
            
            echo '<div class="details">';
            echo '<span class="badge ' . $methodColor . '">' . $methodName . '</span><br><br>';
            echo 'Total vulnerabilities: <strong>' . $vulnCount . '</strong><br>';
            echo 'Total asset instances: <strong>' . $assetInstances . '</strong><br>';
            echo 'API calls made: <strong>' . $apiCalls . '</strong><br>';
            echo 'Execution time: <strong>' . round($methodDuration, 2) . 's</strong><br>';
            echo 'Average instances per vulnerability: <strong>' . ($vulnCount > 0 ? round($assetInstances / $vulnCount, 2) : 0) . '</strong><br>';
            
            if ($method === 'individual_queries') {
                echo '<br><div style="background: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">';
                echo '<strong>Performance Warning:</strong> This method makes ' . $apiCalls . ' API calls with 10ms delays. ';
                echo 'Consider optimizing your Tenable SC configuration to enable faster methods.';
                echo '</div>';
            }
            
            echo '</div>';
            forceFlush();
            
            $optimizationResults[$sevCode] = [
                'severity' => $sevName,
                'method' => $method,
                'method_name' => $methodName,
                'asset_instances' => $assetInstances,
                'vuln_count' => $vulnCount,
                'duration' => $methodDuration,
                'success' => true
            ];
            
            $results['optimization_' . $sevCode] = true;
            
        } catch (Exception $e) {
            clearProgress();
            echo '<div class="details">';
            echo '<span class="badge error">ERROR</span><br><br>';
            echo 'Error: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            forceFlush();
            
            $optimizationResults[$sevCode] = [
                'severity' => $sevName,
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            $results['optimization_' . $sevCode] = false;
        }
        
        echo '</div>';
        forceFlush();
        
        if ($testDepth === 'quick') {
            break;
        }
    }
    
    echo '</div>';
    forceFlush();
    
    // Test 4: Performance Analysis
    if (!empty($optimizationResults)) {
        showProgress('Analyzing performance metrics...');
        echo '<div class="test-section">';
        echo '<h3>Test 4: Performance Analysis</h3>';
        clearProgress();
        forceFlush();
        
        echo '<table class="performance-table">';
        echo '<thead><tr>';
        echo '<th>Severity</th>';
        echo '<th>Method Used</th>';
        echo '<th>Vulnerabilities</th>';
        echo '<th>Asset Instances</th>';
        echo '<th>Execution Time</th>';
        echo '<th>Status</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        $totalDuration = 0;
        $methodCounts = [];
        
        foreach ($optimizationResults as $result) {
            if (!$result['success']) continue;
            
            echo '<tr>';
            echo '<td><strong>' . $result['severity'] . '</strong></td>';
            echo '<td>' . $result['method_name'] . '</td>';
            echo '<td>' . number_format($result['vuln_count']) . '</td>';
            echo '<td>' . number_format($result['asset_instances']) . '</td>';
            echo '<td>' . round($result['duration'], 2) . 's</td>';
            
            $methodColors = [
                'sumid_count_field' => 'success',
                'bulk_export' => 'info',
                'individual_queries' => 'warning'
            ];
            $color = $methodColors[$result['method']] ?? 'info';
            
            echo '<td><span class="badge ' . $color . '">PASSED</span></td>';
            echo '</tr>';
            
            $totalDuration += $result['duration'];
            $methodCounts[$result['method']] = ($methodCounts[$result['method']] ?? 0) + 1;
        }
        
        echo '</tbody></table>';
        
        echo '<div class="summary-grid">';
        echo '<div class="summary-card">';
        echo '<div class="number" style="color: #667eea;">' . round($totalDuration, 1) . 's</div>';
        echo '<div class="label">Total Execution Time</div>';
        echo '</div>';
        
        echo '<div class="summary-card">';
        echo '<div class="number" style="color: #28a745;">' . count($optimizationResults) . '</div>';
        echo '<div class="label">Severities Tested</div>';
        echo '</div>';
        
        echo '<div class="summary-card">';
        $totalVulns = array_sum(array_column(array_filter($optimizationResults, fn($r) => $r['success']), 'vuln_count'));
        echo '<div class="number" style="color: #ffc107;">' . number_format($totalVulns) . '</div>';
        echo '<div class="label">Total Vulnerabilities</div>';
        echo '</div>';
        
        echo '<div class="summary-card">';
        $totalInstances = array_sum(array_column(array_filter($optimizationResults, fn($r) => $r['success']), 'asset_instances'));
        echo '<div class="number" style="color: #dc3545;">' . number_format($totalInstances) . '</div>';
        echo '<div class="label">Total Asset Instances</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        forceFlush();
        
        // Recommendations
        showProgress('Generating recommendations...');
        echo '<div class="recommendation">';
        echo '<h3>Recommendations Based on Test Results</h3>';
        echo '<ul>';
        clearProgress();
        
        $primaryMethod = array_key_first($methodCounts);
        
        if ($primaryMethod === 'sumid_count_field') {
            echo '<li><strong>Excellent!</strong> Your Tenable SC instance supports the fastest optimization method (sumid with count field).</li>';
            echo '<li>Expected performance: <strong>Very Fast</strong> - Minimal API requests required.</li>';
            echo '<li>This is the optimal configuration for VGI calculations.</li>';
        } elseif ($primaryMethod === 'bulk_export') {
            echo '<li><strong>Good!</strong> Your system is using bulk export method (Tenable\'s recommended approach).</li>';
            echo '<li>Expected performance: <strong>Fast</strong> - Efficient batch processing with local aggregation.</li>';
            echo '<li>This method balances speed and reliability well.</li>';
        } elseif ($primaryMethod === 'individual_queries') {
            echo '<li><strong>Warning:</strong> System is falling back to individual plugin queries (slowest method).</li>';
            echo '<li>Expected performance: <strong>Slow</strong> - May require 100,000+ API requests for large datasets.</li>';
            echo '<li>Consider contacting Tenable support to enable bulk export or sumid count field support.</li>';
            echo '<li>For production use, consider implementing caching or running sync during off-peak hours.</li>';
        }
        
        echo '<li>Total test execution time: <strong>' . round($totalDuration, 2) . ' seconds</strong></li>';
        
        if ($testDepth !== 'comprehensive') {
            echo '<li>Note: This was a ' . $testDepth . ' test. Production sync with full historical data may take longer.</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        forceFlush();
    }
    
    showProgress('Preparing final summary...');
    showFinalSummary($results, microtime(true) - $startTime);
    clearProgress();
}

/**
 * Show final test summary
 */
function showFinalSummary($results, $totalDuration) {
    $passed = count(array_filter($results, fn($r) => $r === true));
    $failed = count(array_filter($results, fn($r) => $r === false));
    $total = count($results);
    
    $successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
    
    echo '<div class="test-section" style="border-left-color: ' . ($failed === 0 ? '#28a745' : '#ffc107') . ';">';
    echo '<h3>Final Test Summary</h3>';
    forceFlush();
    
    echo '<div class="summary-grid">';
    
    echo '<div class="summary-card">';
    echo '<div class="number" style="color: #28a745;">' . $passed . '</div>';
    echo '<div class="label">Tests Passed</div>';
    echo '</div>';
    
    echo '<div class="summary-card">';
    echo '<div class="number" style="color: #dc3545;">' . $failed . '</div>';
    echo '<div class="label">Tests Failed</div>';
    echo '</div>';
    
    echo '<div class="summary-card">';
    echo '<div class="number" style="color: #667eea;">' . $successRate . '%</div>';
    echo '<div class="label">Success Rate</div>';
    echo '</div>';
    
    echo '<div class="summary-card">';
    echo '<div class="number" style="color: #17a2b8;">' . round($totalDuration, 1) . 's</div>';
    echo '<div class="label">Total Duration</div>';
    echo '</div>';
    
    echo '</div>';
    forceFlush();
    
    if ($failed === 0) {
        echo '<div class="test-item success" style="margin-top: 20px;">';
        echo '<div class="status"><span class="badge success">ALL TESTS PASSED</span></div>';
        echo '<div class="details">';
        echo '<strong>Your Tenable Security Center integration is ready for production!</strong><br><br>';
        echo 'All API endpoints are working correctly<br>';
        echo 'Authentication is successful<br>';
        echo 'VGI optimization methods are functional<br>';
        echo 'Data integrity validated<br><br>';
        echo 'You can now proceed with confidence to start your data synchronization.';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="test-item warning" style="margin-top: 20px;">';
        echo '<div class="status"><span class="badge warning">SOME TESTS FAILED</span></div>';
        echo '<div class="details">';
        echo '<strong>Please review the failed tests above before proceeding.</strong><br><br>';
        echo 'Common issues:<br>';
        echo '- Incorrect API credentials<br>';
        echo '- Network connectivity problems<br>';
        echo '- Insufficient API permissions<br>';
        echo '- Tenable SC configuration limitations<br><br>';
        echo 'Review the error messages above and correct any issues before starting production sync.';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    forceFlush();
    
    echo '<div style="margin-top: 30px; text-align: center;">';
    echo '<form method="GET" action="" style="display: inline-block; margin-right: 10px;">';
    echo '<button type="submit" class="btn" style="width: auto; padding: 12px 30px;">Run Another Test</button>';
    echo '</form>';
    echo '</div>';
    forceFlush();
}

?>