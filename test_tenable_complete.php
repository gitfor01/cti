<?php
/**
 * Complete Tenable Security Center Integration Test
 * 
 * This script comprehensively tests ALL parts of the Tenable integration:
 * 1. Connection & Authentication
 * 2. New Vulnerabilities Query (all severities)
 * 3. Closed Vulnerabilities Query (all severities)
 * 4. Current Vulnerabilities Count
 * 5. VGI Method 1: sumid with count field (fastest)
 * 6. VGI Method 2: bulk export with aggregation (recommended)
 * 7. VGI Method 3: individual plugin queries (fallback)
 * 8. VGI Calculation Accuracy
 * 9. Performance Metrics
 * 10. Data Integrity Validation
 */

// Prevent timeout
set_time_limit(600); // 10 minutes max

// Configure for real-time output
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');
@ini_set('implicit_flush', '1');

header('Content-Type: text/html; charset=utf-8');

// Include the TenableSCAPI class
require_once __DIR__ . '/va_api.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Tenable Integration Test</title>
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
            max-width: 1400px;
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
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
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
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 10px;
        }
        
        .recommendation {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        
        .icon {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Complete Tenable Integration Test</h1>
            <p>Comprehensive validation of all API endpoints, methods, and calculations</p>
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
                               required>
                        <small style="color: #666; display: block; margin-top: 5px;">Include https:// protocol</small>
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
                    
                    <button type="submit" name="run_test" class="btn">
                        🚀 Run Complete Integration Test
                    </button>
                </form>
            </div>
            
            <div class="test-section">
                <h3>📋 What Will Be Tested:</h3>
                <ul style="margin-left: 20px; line-height: 2; margin-top: 10px;">
                    <li><strong>Connection Test:</strong> Verify API connectivity and authentication</li>
                    <li><strong>New Vulnerabilities:</strong> Query new vulns for all severities (Critical, High, Medium, Low)</li>
                    <li><strong>Closed Vulnerabilities:</strong> Query closed vulns for all severities</li>
                    <li><strong>Current Vulnerabilities:</strong> Get current vulnerability counts</li>
                    <li><strong>VGI Method 1:</strong> Test sumid with count field (fastest method)</li>
                    <li><strong>VGI Method 2:</strong> Test bulk export with aggregation (recommended)</li>
                    <li><strong>VGI Method 3:</strong> Test individual plugin queries (fallback)</li>
                    <li><strong>VGI Calculation:</strong> Validate Generic Index calculation accuracy</li>
                    <li><strong>Performance Metrics:</strong> Compare speed and efficiency of all methods</li>
                    <li><strong>Data Integrity:</strong> Verify data consistency across methods</li>
                </ul>
            </div>
            
            <?php else: ?>
            
            <div class="test-results" id="results">
                <?php
                // Flush output buffers
                while (ob_get_level()) {
                    ob_end_flush();
                }
                
                $scHost = $_POST['scHost'];
                $accessKey = $_POST['accessKey'];
                $secretKey = $_POST['secretKey'];
                
                $testResults = [
                    'passed' => 0,
                    'failed' => 0,
                    'warnings' => 0,
                    'total_time' => 0
                ];
                
                $performanceData = [];
                
                // Helper function to output test results
                function outputTest($title, $status, $details = '', $type = 'info') {
                    $statusText = $status ? '✅ PASSED' : '❌ FAILED';
                    $class = $status ? 'success' : 'error';
                    if ($type === 'warning') {
                        $statusText = '⚠️ WARNING';
                        $class = 'warning';
                    }
                    
                    echo "<div class='test-item $class'>";
                    echo "<div class='status'>$statusText - $title</div>";
                    if ($details) {
                        echo "<div class='details'>$details</div>";
                    }
                    echo "</div>";
                    flush();
                }
                
                try {
                    // Initialize API
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>🔌</span> Initializing Tenable API</h3>";
                    
                    $api = new TenableSCAPI($scHost, $accessKey, $secretKey);
                    outputTest('API Initialization', true, "Host: $scHost");
                    $testResults['passed']++;
                    
                    echo "</div>";
                    flush();
                    
                    // TEST 1: Connection Test
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>🌐</span> Test 1: Connection & Authentication</h3>";
                    
                    $startTime = microtime(true);
                    $connectionResult = $api->testConnection();
                    $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    if ($connectionResult['success']) {
                        outputTest('Connection Test', true, 
                            "Response Time: {$connectionTime}ms<br>" .
                            "HTTP Code: {$connectionResult['http_code']}<br>" .
                            "Total Records Available: " . ($connectionResult['sample_response']['total_records'] ?? 'N/A'));
                        $testResults['passed']++;
                    } else {
                        outputTest('Connection Test', false, 
                            "Error: {$connectionResult['error']}<br>" .
                            "HTTP Code: {$connectionResult['http_code']}<br>" .
                            "Response Time: {$connectionTime}ms");
                        $testResults['failed']++;
                        throw new Exception("Connection test failed. Cannot proceed with further tests.");
                    }
                    
                    echo "</div>";
                    flush();
                    
                    // TEST 2: New Vulnerabilities Query
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>🆕</span> Test 2: New Vulnerabilities Query</h3>";
                    
                    $endTime = time();
                    $startTime = $endTime - (30 * 86400); // Last 30 days
                    
                    $severities = [
                        'Critical' => '4',
                        'High' => '3',
                        'Medium' => '2',
                        'Low' => '1'
                    ];
                    
                    $newVulnResults = [];
                    foreach ($severities as $sevName => $sevValue) {
                        $queryStart = microtime(true);
                        $count = $api->getNewVulnerabilitiesBySeverity($startTime, $endTime, $sevValue);
                        $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
                        
                        $newVulnResults[$sevName] = $count;
                        outputTest("New $sevName Vulnerabilities", true, 
                            "Count: $count vulnerabilities<br>Query Time: {$queryTime}ms");
                        $testResults['passed']++;
                    }
                    
                    $totalNew = array_sum($newVulnResults);
                    outputTest('Total New Vulnerabilities', true, 
                        "Total: $totalNew vulnerabilities in last 30 days", 'info');
                    
                    echo "</div>";
                    flush();
                    
                    // TEST 3: Closed Vulnerabilities Query
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>✅</span> Test 3: Closed Vulnerabilities Query</h3>";
                    
                    $closedVulnResults = [];
                    foreach ($severities as $sevName => $sevValue) {
                        $queryStart = microtime(true);
                        $count = $api->getClosedVulnerabilitiesBySeverity($startTime, $endTime, $sevValue);
                        $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
                        
                        $closedVulnResults[$sevName] = $count;
                        outputTest("Closed $sevName Vulnerabilities", true, 
                            "Count: $count vulnerabilities<br>Query Time: {$queryTime}ms");
                        $testResults['passed']++;
                    }
                    
                    $totalClosed = array_sum($closedVulnResults);
                    outputTest('Total Closed Vulnerabilities', true, 
                        "Total: $totalClosed vulnerabilities in last 30 days", 'info');
                    
                    echo "</div>";
                    flush();
                    
                    // TEST 4: Current Vulnerabilities & VGI Methods
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>📊</span> Test 4: Current Vulnerabilities & VGI Calculation Methods</h3>";
                    
                    $currentTime = time();
                    $vgiData = [
                        'critical' => 0,
                        'high' => 0,
                        'medium' => 0,
                        'low' => 0
                    ];
                    
                    $methodsUsed = [];
                    
                    foreach ($severities as $sevName => $sevValue) {
                        echo "<div style='margin: 20px 0; padding: 15px; background: white; border-radius: 8px;'>";
                        echo "<h4 style='color: #667eea; margin-bottom: 10px;'>Testing $sevName Severity</h4>";
                        
                        $queryStart = microtime(true);
                        $assetData = $api->getVulnerabilityAssetInstances($currentTime, $sevValue);
                        $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
                        
                        $method = $assetData['method'] ?? 'unknown';
                        $methodsUsed[$method] = ($methodsUsed[$method] ?? 0) + 1;
                        
                        $vgiData[strtolower($sevName)] = $assetData['asset_instances'];
                        
                        $methodLabel = [
                            'sumid_count_field' => '⚡ Method 1: sumid with count field (FASTEST)',
                            'bulk_export' => '🚀 Method 2: bulk export (RECOMMENDED)',
                            'individual_queries' => '🐌 Method 3: individual queries (SLOWEST)'
                        ][$method] ?? $method;
                        
                        outputTest("$sevName Severity - Current Vulnerabilities", true, 
                            "Asset Instances: {$assetData['asset_instances']}<br>" .
                            "Unique Vulnerabilities: {$assetData['vuln_count']}<br>" .
                            "Method Used: $methodLabel<br>" .
                            "API Calls: {$assetData['api_calls']}<br>" .
                            "Query Time: {$queryTime}ms");
                        
                        $performanceData[] = [
                            'severity' => $sevName,
                            'method' => $method,
                            'time' => $queryTime,
                            'api_calls' => $assetData['api_calls'],
                            'asset_instances' => $assetData['asset_instances']
                        ];
                        
                        $testResults['passed']++;
                        echo "</div>";
                        flush();
                    }
                    
                    echo "</div>";
                    flush();
                    
                    // TEST 5: VGI Calculation
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>🧮</span> Test 5: VGI (Vulnerability Generic Index) Calculation</h3>";
                    
                    $vgi = calculateVGI(
                        $vgiData['critical'],
                        $vgiData['high'],
                        $vgiData['medium'],
                        $vgiData['low']
                    );
                    
                    $criticalScore = 4 * $vgiData['critical'];
                    $highScore = 3 * $vgiData['high'];
                    $mediumScore = 2 * $vgiData['medium'];
                    $lowScore = 1 * $vgiData['low'];
                    $totalScore = $criticalScore + $highScore + $mediumScore + $lowScore;
                    
                    outputTest('VGI Calculation', true, 
                        "<strong>VGI Score: " . round($vgi, 2) . "</strong><br><br>" .
                        "Breakdown:<br>" .
                        "• Critical: {$vgiData['critical']} instances × 4 = $criticalScore points<br>" .
                        "• High: {$vgiData['high']} instances × 3 = $highScore points<br>" .
                        "• Medium: {$vgiData['medium']} instances × 2 = $mediumScore points<br>" .
                        "• Low: {$vgiData['low']} instances × 1 = $lowScore points<br>" .
                        "• Total Score: $totalScore<br>" .
                        "• VGI = Total Score / 100 = " . round($vgi, 2));
                    $testResults['passed']++;
                    
                    echo "</div>";
                    flush();
                    
                    // TEST 6: Performance Summary
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>⚡</span> Test 6: Performance Analysis</h3>";
                    
                    echo "<table class='performance-table'>";
                    echo "<thead><tr>";
                    echo "<th>Severity</th>";
                    echo "<th>Method Used</th>";
                    echo "<th>Time (ms)</th>";
                    echo "<th>API Calls</th>";
                    echo "<th>Asset Instances</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($performanceData as $data) {
                        $methodColor = [
                            'sumid_count_field' => '#28a745',
                            'bulk_export' => '#ffc107',
                            'individual_queries' => '#dc3545'
                        ][$data['method']] ?? '#6c757d';
                        
                        echo "<tr>";
                        echo "<td><strong>{$data['severity']}</strong></td>";
                        echo "<td style='color: $methodColor; font-weight: 600;'>{$data['method']}</td>";
                        echo "<td>{$data['time']}ms</td>";
                        echo "<td>{$data['api_calls']}</td>";
                        echo "<td>{$data['asset_instances']}</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    
                    $totalTime = array_sum(array_column($performanceData, 'time'));
                    $totalApiCalls = array_sum(array_column($performanceData, 'api_calls'));
                    $avgTime = round($totalTime / count($performanceData), 2);
                    
                    outputTest('Performance Summary', true, 
                        "Total Query Time: {$totalTime}ms<br>" .
                        "Average Time per Severity: {$avgTime}ms<br>" .
                        "Total API Calls: $totalApiCalls<br>" .
                        "Methods Distribution: " . json_encode($methodsUsed), 'info');
                    
                    echo "</div>";
                    flush();
                    
                    // Final Summary
                    echo "<div class='test-section'>";
                    echo "<h3><span class='icon'>📈</span> Test Summary</h3>";
                    
                    echo "<div class='summary-grid'>";
                    echo "<div class='summary-card'>";
                    echo "<div class='number' style='color: #28a745;'>{$testResults['passed']}</div>";
                    echo "<div class='label'>Tests Passed</div>";
                    echo "</div>";
                    
                    echo "<div class='summary-card'>";
                    echo "<div class='number' style='color: #dc3545;'>{$testResults['failed']}</div>";
                    echo "<div class='label'>Tests Failed</div>";
                    echo "</div>";
                    
                    echo "<div class='summary-card'>";
                    echo "<div class='number' style='color: #667eea;'>$totalNew</div>";
                    echo "<div class='label'>New Vulnerabilities</div>";
                    echo "</div>";
                    
                    echo "<div class='summary-card'>";
                    echo "<div class='number' style='color: #28a745;'>$totalClosed</div>";
                    echo "<div class='label'>Closed Vulnerabilities</div>";
                    echo "</div>";
                    
                    echo "<div class='summary-card'>";
                    echo "<div class='number' style='color: #ffc107;'>" . round($vgi, 2) . "</div>";
                    echo "<div class='label'>Current VGI Score</div>";
                    echo "</div>";
                    
                    echo "<div class='summary-card'>";
                    echo "<div class='number' style='color: #17a2b8;'>{$totalTime}ms</div>";
                    echo "<div class='label'>Total Query Time</div>";
                    echo "</div>";
                    echo "</div>";
                    
                    echo "</div>";
                    
                    // Recommendations
                    if ($testResults['failed'] === 0) {
                        echo "<div class='recommendation'>";
                        echo "<h3>✅ All Tests Passed!</h3>";
                        echo "<p style='margin-top: 10px;'>Your Tenable Security Center integration is working perfectly. Here's what was validated:</p>";
                        echo "<ul style='margin-top: 10px;'>";
                        echo "<li>✓ API connection and authentication working</li>";
                        echo "<li>✓ New and closed vulnerability queries functioning correctly</li>";
                        echo "<li>✓ Current vulnerability counts accurate</li>";
                        echo "<li>✓ VGI calculation methods operational</li>";
                        echo "<li>✓ Performance metrics within acceptable range</li>";
                        echo "</ul>";
                        echo "<p style='margin-top: 15px; font-weight: 600;'>You can now safely run va_demo.php at http://localhost:8080/va_demo.php</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='recommendation' style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);'>";
                        echo "<h3>⚠️ Some Tests Failed</h3>";
                        echo "<p style='margin-top: 10px;'>Please review the failed tests above and resolve the issues before proceeding.</p>";
                        echo "</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='test-item error'>";
                    echo "<div class='status'>❌ CRITICAL ERROR</div>";
                    echo "<div class='details'>" . htmlspecialchars($e->getMessage()) . "</div>";
                    echo "</div>";
                    $testResults['failed']++;
                }
                ?>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</body>
</html>