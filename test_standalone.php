<?php
/**
 * Standalone Tenable Test - No Dependencies
 * This file tests if the basic web form works
 */

// Configure for real-time output
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', '1');
set_time_limit(300);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standalone Tenable Test</title>
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
            max-width: 900px;
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
        
        .content {
            padding: 30px;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
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
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .result-box {
            background: #f0fff4;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .result-box h3 {
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .result-box pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Standalone Tenable Test</h1>
            <p>Testing basic form submission and API connectivity</p>
        </div>
        
        <div class="content">
            <?php if (!isset($_POST['run_test'])): ?>
            
            <div class="info-box">
                <strong>ℹ️ Debug Info:</strong><br>
                Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                PHP Version: <?php echo phpversion(); ?><br>
                Request Method: <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
                Script: <?php echo $_SERVER['SCRIPT_NAME']; ?>
            </div>
            
            <div class="form-section">
                <h2 style="margin-bottom: 20px; color: #333;">Enter Tenable SC Credentials</h2>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-group">
                        <label for="scHost">Tenable SC Host URL</label>
                        <input type="text" id="scHost" name="scHost" 
                               placeholder="https://your-tenable-sc.com" 
                               value="https://example.com"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="accessKey">Access Key</label>
                        <input type="text" id="accessKey" name="accessKey" 
                               placeholder="Enter your access key"
                               value="test_access_key" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="secretKey">Secret Key</label>
                        <input type="password" id="secretKey" name="secretKey" 
                               placeholder="Enter your secret key"
                               value="test_secret_key" 
                               required>
                    </div>
                    
                    <button type="submit" name="run_test" value="1" class="btn">
                        🚀 Test Form Submission
                    </button>
                </form>
            </div>
            
            <?php else: ?>
            
            <div class="result-box">
                <h3>✅ Form Submission Successful!</h3>
                <p><strong>POST data received correctly. Your server configuration is working!</strong></p>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Received Data:</h4>
                <pre><?php
                echo "Host: " . htmlspecialchars($_POST['scHost'] ?? 'Not set') . "\n";
                echo "Access Key: " . htmlspecialchars($_POST['accessKey'] ?? 'Not set') . "\n";
                echo "Secret Key: " . str_repeat('*', strlen($_POST['secretKey'] ?? '')) . " (hidden)\n";
                echo "\nRequest Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
                echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";
                ?></pre>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Next Steps:</h4>
                <p>Since form submission works, let's test the actual Tenable API connection...</p>
                
                <?php
                // Try to include va_api.php
                $apiFile = __DIR__ . '/va_api.php';
                if (file_exists($apiFile)) {
                    echo "<p style='color: green; margin-top: 10px;'>✅ va_api.php found at: $apiFile</p>";
                    
                    try {
                        require_once $apiFile;
                        echo "<p style='color: green;'>✅ va_api.php loaded successfully</p>";
                        
                        if (class_exists('TenableSCAPI')) {
                            echo "<p style='color: green;'>✅ TenableSCAPI class is available</p>";
                            
                            // Try to create API instance
                            try {
                                $api = new TenableSCAPI(
                                    $_POST['scHost'],
                                    $_POST['accessKey'],
                                    $_POST['secretKey']
                                );
                                echo "<p style='color: green;'>✅ TenableSCAPI instance created</p>";
                                
                                // Test connection
                                echo "<h4 style='margin-top: 20px;'>Testing API Connection...</h4>";
                                flush();
                                
                                $result = $api->testConnection();
                                
                                echo "<pre>";
                                echo "Connection Result:\n";
                                echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
                                echo "HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
                                echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
                                if (isset($result['error'])) {
                                    echo "Error: " . $result['error'] . "\n";
                                }
                                echo "</pre>";
                                
                            } catch (Exception $e) {
                                echo "<p style='color: red;'>❌ Error creating API instance: " . htmlspecialchars($e->getMessage()) . "</p>";
                            }
                        } else {
                            echo "<p style='color: red;'>❌ TenableSCAPI class not found in va_api.php</p>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>❌ Error loading va_api.php: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ va_api.php not found at: $apiFile</p>";
                }
                ?>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn" style="display: inline-block; text-decoration: none; text-align: center;">
                        ← Back to Form
                    </a>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</body>
</html>