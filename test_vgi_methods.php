<?php
/**
 * VGI Optimization Methods Test Script
 * 
 * Tests which of the 3 VGI calculation methods work with your Tenable SC instance:
 * - Method 1: sumid with count field (fastest)
 * - Method 2: Bulk export with aggregation (recommended)
 * - Method 3: Individual queries (slowest fallback)
 * 
 * This helps determine the optimal method for your environment.
 */

// Prevent timeout
set_time_limit(300); // 5 minutes max

// Configure for real-time output
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');
@ini_set('implicit_flush', '1');

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VGI Methods Test - Tenable SC</title>
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
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
        
        .method-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        
        .method-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .method-section.success {
            border-left-color: #28a745;
            background: #f0fff4;
        }
        
        .method-section.error {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .method-section.testing {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-left: auto;
        }
        
        .status-badge.success {
            background: #28a745;
            color: white;
        }
        
        .status-badge.error {
            background: #dc3545;
            color: white;
        }
        
        .status-badge.testing {
            background: #ffc107;
            color: #333;
        }
        
        .status-badge.pending {
            background: #6c757d;
            color: white;
        }
        
        .method-details {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.8;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
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
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .recommendation h3 {
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .recommendation ul {
            margin-left: 20px;
            line-height: 2;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box h4 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
            line-height: 1.8;
            color: #333;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .comparison-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .comparison-table tr:last-child td {
            border-bottom: none;
        }
        
        .comparison-table tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔬 VGI Optimization Methods Test</h1>
            <p>Test which VGI calculation method works best with your Tenable Security Center</p>
        </div>
        
        <div class="content">
            <?php if (!isset($_POST['run_test'])): ?>
            
            <div class="info-box">
                <h4>📋 What This Test Does:</h4>
                <ul>
                    <li><strong>Method 1:</strong> Tests if your Tenable SC supports the <code>sumid</code> tool with <code>count</code> field (fastest method)</li>
                    <li><strong>Method 2:</strong> Tests bulk export using <code>vulndetails</code> tool with aggregation (recommended method)</li>
                    <li><strong>Method 3:</strong> Tests individual vulnerability queries (slowest but most compatible)</li>
                </ul>
            </div>
            
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
                    
                    <div class="form-group">
                        <label for="severity">Test Severity Level</label>
                        <select id="severity" name="severity" required>
                            <option value="4">Critical</option>
                            <option value="3" selected>High</option>
                            <option value="2">Medium</option>
                            <option value="1">Low</option>
                        </select>
                        <small style="color: #666; display: block; margin-top: 5px;">Choose which severity level to test (High is recommended for balanced results)</small>
                    </div>
                    
                    <button type="submit" name="run_test" class="btn">
                        🚀 Test VGI Methods
                    </button>
                </form>
            </div>
            
            <div class="method-section">
                <h3>📊 Method Comparison:</h3>
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Tool</th>
                            <th>Speed</th>
                            <th>API Calls</th>
                            <th>Compatibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Method 1</strong></td>
                            <td><code>sumid</code> with count</td>
                            <td>⚡ Fastest (1-5s)</td>
                            <td>1-2 calls</td>
                            <td>⚠️ May not be available</td>
                        </tr>
                        <tr>
                            <td><strong>Method 2</strong></td>
                            <td><code>vulndetails</code> bulk</td>
                            <td>🚀 Fast (10-30s)</td>
                            <td>5-20 calls</td>
                            <td>✅ Usually available</td>
                        </tr>
                        <tr>
                            <td><strong>Method 3</strong></td>
                            <td><code>sumid</code> + individual</td>
                            <td>🐌 Slow (60-300s)</td>
                            <td>100+ calls</td>
                            <td>✅ Always works</td>
                        </tr>
                    </tbody>
                </table>
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
                $severity = $_POST['severity'];
                
                $severityNames = [
                    '4' => 'Critical',
                    '3' => 'High',
                    '2' => 'Medium',
                    '1' => 'Low'
                ];
                
                echo "<div class='info-box'>";
                echo "<h4>🎯 Test Configuration:</h4>";
                echo "<ul>";
                echo "<li><strong>Host:</strong> " . htmlspecialchars($scHost) . "</li>";
                echo "<li><strong>Severity:</strong> " . $severityNames[$severity] . "</li>";
                echo "<li><strong>Time Range:</strong> Last 30 days</li>";
                echo "</ul>";
                echo "</div>";
                
                flush();
                
                // Initialize test class
                $tester = new VGIMethodTester($scHost, $accessKey, $secretKey);
                
                // Test connection first
                echo "<div class='method-section testing'>";
                echo "<h3><span class='spinner'></span>Testing Connection...</h3>";
                echo "</div>";
                flush();
                
                $connectionTest = $tester->testConnection();
                
                if (!$connectionTest['success']) {
                    echo "<script>document.querySelector('.method-section.testing').className = 'method-section error';</script>";
                    echo "<script>document.querySelector('.method-section.testing h3').innerHTML = '❌ Connection Failed';</script>";
                    echo "<div class='method-details'>";
                    echo "<div class='detail-row'><span class='detail-label'>Error:</span><span class='detail-value'>" . htmlspecialchars($connectionTest['error']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>HTTP Code:</span><span class='detail-value'>" . $connectionTest['http_code'] . "</span></div>";
                    echo "</div>";
                    echo "</div>";
                    exit;
                }
                
                echo "<script>document.querySelector('.method-section.testing').className = 'method-section success';</script>";
                echo "<script>document.querySelector('.method-section.testing h3').innerHTML = '✅ Connection Successful <span class=\"status-badge success\">PASSED</span>';</script>";
                echo "<div class='method-details'>";
                echo "<div class='detail-row'><span class='detail-label'>Response Time:</span><span class='detail-value'>" . $connectionTest['response_time_ms'] . " ms</span></div>";
                echo "<div class='detail-row'><span class='detail-label'>HTTP Code:</span><span class='detail-value'>" . $connectionTest['http_code'] . "</span></div>";
                echo "</div>";
                echo "</div>";
                flush();
                
                // Prepare test parameters
                $endTime = time();
                $startTime = $endTime - (30 * 86400); // Last 30 days
                
                $queryFilters = [
                    [
                        'filterName' => 'lastSeen',
                        'operator' => '=',
                        'value' => $endTime . ':' . $startTime
                    ],
                    [
                        'filterName' => 'severity',
                        'operator' => '=',
                        'value' => $severity
                    ]
                ];
                
                // Test Method 1
                echo "<div class='method-section testing' id='method1'>";
                echo "<h3><span class='spinner'></span>Method 1: sumid with count field <span class='status-badge testing'>TESTING</span></h3>";
                echo "<div class='method-details'>";
                echo "<div class='detail-row'><span class='detail-label'>Tool:</span><span class='detail-value'>sumid</span></div>";
                echo "<div class='detail-row'><span class='detail-label'>Expected Speed:</span><span class='detail-value'>⚡ Fastest (1-5 seconds)</span></div>";
                echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value'>Testing...</span></div>";
                echo "</div>";
                echo "</div>";
                flush();
                
                $method1Result = $tester->testMethod1($queryFilters);
                
                if ($method1Result['success']) {
                    echo "<script>document.getElementById('method1').className = 'method-section success';</script>";
                    echo "<script>document.getElementById('method1').querySelector('h3').innerHTML = '✅ Method 1: sumid with count field <span class=\"status-badge success\">WORKS!</span>';</script>";
                    echo "<script>document.getElementById('method1').querySelector('.method-details').innerHTML = `";
                    echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value' style='color: #28a745; font-weight: 600;'>✅ AVAILABLE</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Execution Time:</span><span class='detail-value'>" . $method1Result['execution_time'] . " seconds</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Asset Instances Found:</span><span class='detail-value'>" . number_format($method1Result['asset_instances']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Unique Vulnerabilities:</span><span class='detail-value'>" . number_format($method1Result['vuln_count']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>API Calls Made:</span><span class='detail-value'>" . $method1Result['api_calls'] . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Recommendation:</span><span class='detail-value' style='color: #28a745; font-weight: 600;'>⚡ USE THIS METHOD (Fastest!)</span></div>";
                    echo "`;</script>";
                } else {
                    echo "<script>document.getElementById('method1').className = 'method-section error';</script>";
                    echo "<script>document.getElementById('method1').querySelector('h3').innerHTML = '❌ Method 1: sumid with count field <span class=\"status-badge error\">NOT AVAILABLE</span>';</script>";
                    echo "<script>document.getElementById('method1').querySelector('.method-details').innerHTML = `";
                    echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value' style='color: #dc3545; font-weight: 600;'>❌ NOT AVAILABLE</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Reason:</span><span class='detail-value'>" . htmlspecialchars($method1Result['error']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Execution Time:</span><span class='detail-value'>" . $method1Result['execution_time'] . " seconds</span></div>";
                    echo "`;</script>";
                }
                flush();
                
                // Test Method 2
                echo "<div class='method-section testing' id='method2'>";
                echo "<h3><span class='spinner'></span>Method 2: Bulk export with aggregation <span class='status-badge testing'>TESTING</span></h3>";
                echo "<div class='method-details'>";
                echo "<div class='detail-row'><span class='detail-label'>Tool:</span><span class='detail-value'>vulndetails</span></div>";
                echo "<div class='detail-row'><span class='detail-label'>Expected Speed:</span><span class='detail-value'>🚀 Fast (10-30 seconds)</span></div>";
                echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value'>Testing...</span></div>";
                echo "</div>";
                echo "</div>";
                flush();
                
                $method2Result = $tester->testMethod2($queryFilters);
                
                if ($method2Result['success']) {
                    echo "<script>document.getElementById('method2').className = 'method-section success';</script>";
                    echo "<script>document.getElementById('method2').querySelector('h3').innerHTML = '✅ Method 2: Bulk export with aggregation <span class=\"status-badge success\">WORKS!</span>';</script>";
                    echo "<script>document.getElementById('method2').querySelector('.method-details').innerHTML = `";
                    echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value' style='color: #28a745; font-weight: 600;'>✅ AVAILABLE</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Execution Time:</span><span class='detail-value'>" . $method2Result['execution_time'] . " seconds</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Asset Instances Found:</span><span class='detail-value'>" . number_format($method2Result['asset_instances']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Unique Vulnerabilities:</span><span class='detail-value'>" . number_format($method2Result['vuln_count']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>API Calls Made:</span><span class='detail-value'>" . $method2Result['api_calls'] . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Recommendation:</span><span class='detail-value' style='color: #28a745; font-weight: 600;'>🚀 Good fallback option</span></div>";
                    echo "`;</script>";
                } else {
                    echo "<script>document.getElementById('method2').className = 'method-section error';</script>";
                    echo "<script>document.getElementById('method2').querySelector('h3').innerHTML = '❌ Method 2: Bulk export with aggregation <span class=\"status-badge error\">NOT AVAILABLE</span>';</script>";
                    echo "<script>document.getElementById('method2').querySelector('.method-details').innerHTML = `";
                    echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value' style='color: #dc3545; font-weight: 600;'>❌ NOT AVAILABLE</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Reason:</span><span class='detail-value'>" . htmlspecialchars($method2Result['error']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Execution Time:</span><span class='detail-value'>" . $method2Result['execution_time'] . " seconds</span></div>";
                    echo "`;</script>";
                }
                flush();
                
                // Test Method 3
                echo "<div class='method-section testing' id='method3'>";
                echo "<h3><span class='spinner'></span>Method 3: Individual queries <span class='status-badge testing'>TESTING</span></h3>";
                echo "<div class='method-details'>";
                echo "<div class='detail-row'><span class='detail-label'>Tool:</span><span class='detail-value'>sumid + individual queries</span></div>";
                echo "<div class='detail-row'><span class='detail-label'>Expected Speed:</span><span class='detail-value'>🐌 Slow (60-300 seconds)</span></div>";
                echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value'>Testing (limited to 10 vulnerabilities)...</span></div>";
                echo "</div>";
                echo "</div>";
                flush();
                
                $method3Result = $tester->testMethod3($queryFilters, $endTime);
                
                if ($method3Result['success']) {
                    echo "<script>document.getElementById('method3').className = 'method-section success';</script>";
                    echo "<script>document.getElementById('method3').querySelector('h3').innerHTML = '✅ Method 3: Individual queries <span class=\"status-badge success\">WORKS!</span>';</script>";
                    echo "<script>document.getElementById('method3').querySelector('.method-details').innerHTML = `";
                    echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value' style='color: #28a745; font-weight: 600;'>✅ AVAILABLE</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Execution Time:</span><span class='detail-value'>" . $method3Result['execution_time'] . " seconds (limited test)</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Asset Instances Found:</span><span class='detail-value'>" . number_format($method3Result['asset_instances']) . " (from " . $method3Result['tested_vulns'] . " vulns)</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Unique Vulnerabilities:</span><span class='detail-value'>" . number_format($method3Result['vuln_count']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>API Calls Made:</span><span class='detail-value'>" . $method3Result['api_calls'] . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Recommendation:</span><span class='detail-value' style='color: #ffc107; font-weight: 600;'>⚠️ Use only as last resort (very slow)</span></div>";
                    echo "`;</script>";
                } else {
                    echo "<script>document.getElementById('method3').className = 'method-section error';</script>";
                    echo "<script>document.getElementById('method3').querySelector('h3').innerHTML = '❌ Method 3: Individual queries <span class=\"status-badge error\">FAILED</span>';</script>";
                    echo "<script>document.getElementById('method3').querySelector('.method-details').innerHTML = `";
                    echo "<div class='detail-row'><span class='detail-label'>Status:</span><span class='detail-value' style='color: #dc3545; font-weight: 600;'>❌ FAILED</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Reason:</span><span class='detail-value'>" . htmlspecialchars($method3Result['error']) . "</span></div>";
                    echo "<div class='detail-row'><span class='detail-label'>Execution Time:</span><span class='detail-value'>" . $method3Result['execution_time'] . " seconds</span></div>";
                    echo "`;</script>";
                }
                flush();
                
                // Generate recommendation
                $workingMethods = [];
                if ($method1Result['success']) $workingMethods[] = 'Method 1 (sumid with count)';
                if ($method2Result['success']) $workingMethods[] = 'Method 2 (bulk export)';
                if ($method3Result['success']) $workingMethods[] = 'Method 3 (individual queries)';
                
                echo "<div class='recommendation'>";
                echo "<h3>🎯 Recommendation for Your Environment:</h3>";
                
                if (empty($workingMethods)) {
                    echo "<p style='font-size: 18px; margin-top: 10px;'>⚠️ <strong>No methods are working!</strong> Please check your API credentials and permissions.</p>";
                } else {
                    echo "<p style='font-size: 18px; margin-top: 10px;'><strong>Working Methods:</strong> " . implode(', ', $workingMethods) . "</p>";
                    echo "<ul style='margin-top: 15px;'>";
                    
                    if ($method1Result['success']) {
                        echo "<li><strong>✅ BEST CHOICE: Method 1</strong> - Your Tenable SC supports the fastest method! Use this for optimal performance.</li>";
                        echo "<li><strong>Speed:</strong> " . $method1Result['execution_time'] . "s vs " . ($method2Result['success'] ? $method2Result['execution_time'] . "s" : "N/A") . " (Method 2) - " . 
                             ($method2Result['success'] ? round(($method2Result['execution_time'] / $method1Result['execution_time']), 1) . "x faster!" : "Much faster!") . "</li>";
                    } elseif ($method2Result['success']) {
                        echo "<li><strong>✅ RECOMMENDED: Method 2</strong> - Use bulk export method for good performance.</li>";
                        echo "<li><strong>Speed:</strong> " . $method2Result['execution_time'] . "s - Acceptable performance for most use cases.</li>";
                    } elseif ($method3Result['success']) {
                        echo "<li><strong>⚠️ FALLBACK: Method 3</strong> - Only individual queries work. Expect slower performance.</li>";
                        echo "<li><strong>Warning:</strong> Full scans may take several minutes. Consider limiting the scope.</li>";
                    }
                    
                    echo "<li><strong>Implementation:</strong> The system will automatically use the best available method.</li>";
                    echo "</ul>";
                }
                
                echo "</div>";
                
                // Performance comparison
                if (count($workingMethods) > 1) {
                    echo "<div class='method-section'>";
                    echo "<h3>📊 Performance Comparison:</h3>";
                    echo "<table class='comparison-table'>";
                    echo "<thead><tr><th>Method</th><th>Status</th><th>Time</th><th>Asset Instances</th><th>API Calls</th><th>Efficiency</th></tr></thead>";
                    echo "<tbody>";
                    
                    if ($method1Result['success']) {
                        echo "<tr>";
                        echo "<td><strong>Method 1</strong></td>";
                        echo "<td><span class='status-badge success'>WORKS</span></td>";
                        echo "<td>" . $method1Result['execution_time'] . "s</td>";
                        echo "<td>" . number_format($method1Result['asset_instances']) . "</td>";
                        echo "<td>" . $method1Result['api_calls'] . "</td>";
                        echo "<td>⚡⚡⚡ Excellent</td>";
                        echo "</tr>";
                    }
                    
                    if ($method2Result['success']) {
                        echo "<tr>";
                        echo "<td><strong>Method 2</strong></td>";
                        echo "<td><span class='status-badge success'>WORKS</span></td>";
                        echo "<td>" . $method2Result['execution_time'] . "s</td>";
                        echo "<td>" . number_format($method2Result['asset_instances']) . "</td>";
                        echo "<td>" . $method2Result['api_calls'] . "</td>";
                        echo "<td>🚀🚀 Good</td>";
                        echo "</tr>";
                    }
                    
                    if ($method3Result['success']) {
                        echo "<tr>";
                        echo "<td><strong>Method 3</strong></td>";
                        echo "<td><span class='status-badge success'>WORKS</span></td>";
                        echo "<td>" . $method3Result['execution_time'] . "s*</td>";
                        echo "<td>" . number_format($method3Result['asset_instances']) . "*</td>";
                        echo "<td>" . $method3Result['api_calls'] . "*</td>";
                        echo "<td>🐌 Slow</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody>";
                    echo "</table>";
                    echo "<p style='margin-top: 10px; color: #666; font-size: 14px;'>* Method 3 was tested with limited vulnerabilities. Full scan would be significantly slower.</p>";
                    echo "</div>";
                }
                
                ?>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php

/**
 * VGI Method Tester Class
 * Tests each VGI calculation method independently
 */
class VGIMethodTester {
    private $host;
    private $accessKey;
    private $secretKey;
    
    const PAGE_SIZE = 1000;
    const MAX_TEST_VULNS = 10; // Limit for Method 3 testing
    
    public function __construct($host, $accessKey, $secretKey) {
        $this->host = rtrim($host, '/');
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    
    /**
     * Test connection to Tenable SC
     */
    public function testConnection() {
        $startTime = microtime(true);
        $debugInfo = [
            'success' => false,
            'http_code' => null,
            'response_time_ms' => 0,
            'error' => null
        ];
        
        try {
            $testEndTime = time();
            $testStartTime = $testEndTime - (30 * 86400); // Last 30 days
            
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'listvuln',
                    'type' => 'vuln',
                    'filters' => [
                        [
                            'filterName' => 'lastSeen',
                            'operator' => '=',
                            'value' => $testEndTime . ':' . $testStartTime
                        ]
                    ]
                ],
                'startOffset' => 0,
                'endOffset' => 1
            ];
            
            $response = $this->makeRequest('/analysis', 'POST', $requestData);
            
            $debugInfo['success'] = true;
            $debugInfo['http_code'] = 200;
            $debugInfo['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            
        } catch (Exception $e) {
            $debugInfo['error'] = $e->getMessage();
            $debugInfo['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
        }
        
        return $debugInfo;
    }
    
    /**
     * Test Method 1: sumid with count field
     */
    public function testMethod1($queryFilters) {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'execution_time' => 0,
            'asset_instances' => 0,
            'vuln_count' => 0,
            'api_calls' => 0,
            'error' => null
        ];
        
        try {
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'sumid',
                    'type' => 'vuln',
                    'filters' => $queryFilters
                ],
                'sortField' => 'severity',
                'sortDirection' => 'desc',
                'startOffset' => 0,
                'endOffset' => 10
            ];
            
            $response = $this->makeRequest('/analysis', 'POST', $requestData);
            $result['api_calls']++;
            
            if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                $result['error'] = 'Invalid response structure from sumid query';
                $result['execution_time'] = round(microtime(true) - $startTime, 2);
                return $result;
            }
            
            $results = $response['response']['results'];
            if (empty($results)) {
                $result['success'] = true;
                $result['execution_time'] = round(microtime(true) - $startTime, 2);
                return $result;
            }
            
            $firstResult = $results[0];
            if (!isset($firstResult['count'])) {
                $result['error'] = "sumid does NOT return 'count' field. Available fields: " . implode(', ', array_keys($firstResult));
                $result['execution_time'] = round(microtime(true) - $startTime, 2);
                return $result;
            }
            
            // Count field exists! Proceed with full query
            $totalAssetInstances = 0;
            $totalVulnCount = 0;
            $offset = 0;
            $hasMore = true;
            
            while ($hasMore && $offset < 5000) {
                $requestData['startOffset'] = $offset;
                $requestData['endOffset'] = $offset + self::PAGE_SIZE;
                
                $response = $this->makeRequest('/analysis', 'POST', $requestData);
                $result['api_calls']++;
                
                if (!isset($response['response']['results'])) {
                    break;
                }
                
                $results = $response['response']['results'];
                
                foreach ($results as $vuln) {
                    $totalAssetInstances += (int)$vuln['count'];
                    $totalVulnCount++;
                }
                
                $totalRecords = isset($response['response']['totalRecords']) 
                    ? (int)$response['response']['totalRecords'] 
                    : 0;
                
                $offset += self::PAGE_SIZE;
                $hasMore = ($offset < $totalRecords) && (count($results) === self::PAGE_SIZE);
            }
            
            $result['success'] = true;
            $result['asset_instances'] = $totalAssetInstances;
            $result['vuln_count'] = $totalVulnCount;
            $result['execution_time'] = round(microtime(true) - $startTime, 2);
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['execution_time'] = round(microtime(true) - $startTime, 2);
        }
        
        return $result;
    }
    
    /**
     * Test Method 2: Bulk export with aggregation
     */
    public function testMethod2($queryFilters) {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'execution_time' => 0,
            'asset_instances' => 0,
            'vuln_count' => 0,
            'api_calls' => 0,
            'error' => null
        ];
        
        try {
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'vulndetails',
                    'type' => 'vuln',
                    'filters' => $queryFilters
                ],
                'sortField' => 'severity',
                'sortDirection' => 'desc',
                'startOffset' => 0,
                'endOffset' => self::PAGE_SIZE
            ];
            
            $vulnData = [];
            $offset = 0;
            $hasMore = true;
            $maxPages = 5; // Limit for testing
            
            while ($hasMore && $result['api_calls'] < $maxPages) {
                $requestData['startOffset'] = $offset;
                $requestData['endOffset'] = $offset + self::PAGE_SIZE;
                
                $response = $this->makeRequest('/analysis', 'POST', $requestData);
                $result['api_calls']++;
                
                if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                    break;
                }
                
                $results = $response['response']['results'];
                
                foreach ($results as $finding) {
                    if (!isset($finding['pluginID']) || !isset($finding['ip'])) {
                        continue;
                    }
                    
                    $pluginID = $finding['pluginID'];
                    $ip = $finding['ip'];
                    
                    if (!isset($vulnData[$pluginID])) {
                        $vulnData[$pluginID] = ['ips' => []];
                    }
                    
                    if (!in_array($ip, $vulnData[$pluginID]['ips'])) {
                        $vulnData[$pluginID]['ips'][] = $ip;
                    }
                }
                
                $offset += self::PAGE_SIZE;
                
                if (count($results) < self::PAGE_SIZE) {
                    $hasMore = false;
                }
            }
            
            $totalAssetInstances = 0;
            foreach ($vulnData as $pluginID => $data) {
                $totalAssetInstances += count($data['ips']);
            }
            
            $result['success'] = true;
            $result['asset_instances'] = $totalAssetInstances;
            $result['vuln_count'] = count($vulnData);
            $result['execution_time'] = round(microtime(true) - $startTime, 2);
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['execution_time'] = round(microtime(true) - $startTime, 2);
        }
        
        return $result;
    }
    
    /**
     * Test Method 3: Individual queries (limited test)
     */
    public function testMethod3($queryFilters, $endTime) {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'execution_time' => 0,
            'asset_instances' => 0,
            'vuln_count' => 0,
            'tested_vulns' => 0,
            'api_calls' => 0,
            'error' => null
        ];
        
        try {
            // First, get list of unique vulnerabilities
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'sumid',
                    'type' => 'vuln',
                    'filters' => $queryFilters
                ],
                'sortField' => 'severity',
                'sortDirection' => 'desc',
                'startOffset' => 0,
                'endOffset' => self::MAX_TEST_VULNS
            ];
            
            $response = $this->makeRequest('/analysis', 'POST', $requestData);
            $result['api_calls']++;
            
            if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                $result['error'] = 'Cannot get vulnerability list';
                $result['execution_time'] = round(microtime(true) - $startTime, 2);
                return $result;
            }
            
            $results = $response['response']['results'];
            $uniqueVulns = [];
            
            foreach ($results as $vuln) {
                if (isset($vuln['pluginID'])) {
                    $uniqueVulns[] = $vuln['pluginID'];
                }
            }
            
            if (empty($uniqueVulns)) {
                $result['success'] = true;
                $result['execution_time'] = round(microtime(true) - $startTime, 2);
                return $result;
            }
            
            // Query each vulnerability individually (limited to first 10)
            $totalAssetInstances = 0;
            $testedCount = 0;
            
            foreach (array_slice($uniqueVulns, 0, self::MAX_TEST_VULNS) as $pluginID) {
                $vulnFilters = array_merge($queryFilters, [
                    [
                        'filterName' => 'pluginID',
                        'operator' => '=',
                        'value' => $pluginID
                    ]
                ]);
                
                $vulnRequest = [
                    'type' => 'vuln',
                    'sourceType' => 'cumulative',
                    'query' => [
                        'tool' => 'vulnipdetail',
                        'type' => 'vuln',
                        'filters' => $vulnFilters
                    ],
                    'startOffset' => 0,
                    'endOffset' => 1000
                ];
                
                try {
                    $vulnResponse = $this->makeRequest('/analysis', 'POST', $vulnRequest);
                    $result['api_calls']++;
                    
                    if (isset($vulnResponse['response']['results'])) {
                        $uniqueIPs = [];
                        foreach ($vulnResponse['response']['results'] as $instance) {
                            if (isset($instance['ip'])) {
                                $uniqueIPs[$instance['ip']] = true;
                            }
                        }
                        $totalAssetInstances += count($uniqueIPs);
                    }
                    
                    $testedCount++;
                } catch (Exception $e) {
                    // Skip this vulnerability if query fails
                    continue;
                }
            }
            
            $result['success'] = true;
            $result['asset_instances'] = $totalAssetInstances;
            $result['vuln_count'] = count($uniqueVulns);
            $result['tested_vulns'] = $testedCount;
            $result['execution_time'] = round(microtime(true) - $startTime, 2);
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['execution_time'] = round(microtime(true) - $startTime, 2);
        }
        
        return $result;
    }
    
    /**
     * Make API request to Tenable SC
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->host . '/rest' . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $headers = [
            'x-apikey: accesskey=' . $this->accessKey . '; secretkey=' . $this->secretKey . ';',
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Connection error: ' . $error);
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API request failed with status code: $httpCode. Response: " . substr($response, 0, 200));
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        return $decoded;
    }
}