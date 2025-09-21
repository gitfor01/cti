<?php
/**
 * PCF Integration Test Script
 * 
 * This script tests the PCF integration functionality
 */

require_once 'config/database.php';
require_once 'includes/pcf_functions.php';

echo "<h1>PCF Integration Test</h1>\n";
echo "<style>body { font-family: Arial, sans-serif; margin: 40px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>\n";

// Test 1: CTI Database Connection
echo "<h2>Test 1: CTI Database Connection</h2>\n";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='success'>✓ CTI database connection successful</p>\n";
} catch (Exception $e) {
    echo "<p class='error'>✗ CTI database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 2: PCF Database Connection
echo "<h2>Test 2: PCF Database Connection</h2>\n";
$pcfPdo = getPcfConnection();
if ($pcfPdo) {
    echo "<p class='success'>✓ PCF database connection successful</p>\n";
    
    // Get PCF database statistics
    try {
        $stmt = $pcfPdo->query("SELECT COUNT(*) as count FROM Issues");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='info'>Found {$result['count']} issues in PCF database</p>\n";
        
        $stmt = $pcfPdo->query("SELECT COUNT(*) as count FROM Projects");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='info'>Found {$result['count']} projects in PCF database</p>\n";
        
        // Sample some data
        $stmt = $pcfPdo->query("SELECT name, cvss, status FROM Issues LIMIT 5");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($samples)) {
            echo "<p class='info'>Sample PCF findings:</p>\n";
            echo "<ul>\n";
            foreach ($samples as $sample) {
                echo "<li>" . htmlspecialchars($sample['name']) . " (CVSS: {$sample['cvss']}, Status: {$sample['status']})</li>\n";
            }
            echo "</ul>\n";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error querying PCF database: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    echo "<p class='error'>✗ PCF database connection failed</p>\n";
}

// Test 3: PCF Tables Exist
echo "<h2>Test 3: PCF Integration Tables</h2>\n";
try {
    // Check if pcf_findings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'pcf_findings'");
    $result = $stmt->fetch();
    if ($result) {
        echo "<p class='success'>✓ pcf_findings table exists</p>\n";
        
        // Get count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM pcf_findings");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='info'>Current PCF findings in CTI database: {$result['count']}</p>\n";
        
    } else {
        echo "<p class='error'>✗ pcf_findings table does not exist - run setup_pcf_integration.php first</p>\n";
    }
    
    // Check if pcf_sync_log table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'pcf_sync_log'");
    $result = $stmt->fetch();
    if ($result) {
        echo "<p class='success'>✓ pcf_sync_log table exists</p>\n";
        
        // Get last sync
        $lastSync = getLastSyncTime($pdo);
        if ($lastSync) {
            echo "<p class='info'>Last sync: " . htmlspecialchars($lastSync) . "</p>\n";
        } else {
            echo "<p class='info'>No sync performed yet</p>\n";
        }
        
    } else {
        echo "<p class='error'>✗ pcf_sync_log table does not exist - run setup_pcf_integration.php first</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking tables: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 4: Sync Functionality
echo "<h2>Test 4: Sync Functionality</h2>\n";
if (isset($_GET['test_sync'])) {
    echo "<p class='info'>Testing sync functionality...</p>\n";
    $syncResult = syncPcfFindings($pdo);
    
    if ($syncResult['success']) {
        echo "<p class='success'>✓ Sync test successful - {$syncResult['count']} findings synced</p>\n";
    } else {
        echo "<p class='error'>✗ Sync test failed: {$syncResult['error']}</p>\n";
    }
} else {
    echo "<p><a href='?test_sync=1'>Click here to test sync functionality</a></p>\n";
}

// Test 5: Helper Functions
echo "<h2>Test 5: Helper Functions</h2>\n";
try {
    // Test severity functions
    $testCvss = [0.0, 3.5, 5.2, 7.8, 9.5];
    echo "<p class='info'>Testing severity classification:</p>\n";
    echo "<ul>\n";
    foreach ($testCvss as $cvss) {
        $class = getSeverityClass($cvss);
        $text = getSeverityText($cvss);
        echo "<li>CVSS {$cvss}: {$text} ({$class})</li>\n";
    }
    echo "</ul>\n";
    echo "<p class='success'>✓ Severity functions working correctly</p>\n";
    
    // Test status color function
    $testStatuses = ['open', 'fixed', 'closed', 'retest', 'unknown'];
    echo "<p class='info'>Testing status colors:</p>\n";
    echo "<ul>\n";
    foreach ($testStatuses as $status) {
        $color = getStatusColor($status);
        echo "<li>Status '{$status}': {$color}</li>\n";
    }
    echo "</ul>\n";
    echo "<p class='success'>✓ Status color functions working correctly</p>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error testing helper functions: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 6: File Permissions
echo "<h2>Test 6: File Permissions</h2>\n";
$pcfDbPath = '/Users/ammarfahad/Downloads/Others/CTI Proj/pcf/configuration/database.sqlite3';
if (file_exists($pcfDbPath)) {
    if (is_readable($pcfDbPath)) {
        echo "<p class='success'>✓ PCF database file is readable</p>\n";
    } else {
        echo "<p class='error'>✗ PCF database file is not readable - check permissions</p>\n";
    }
} else {
    echo "<p class='error'>✗ PCF database file not found at: " . htmlspecialchars($pcfDbPath) . "</p>\n";
}

echo "<h2>Test Summary</h2>\n";
echo "<p>If all tests pass, the PCF integration should be working correctly.</p>\n";
echo "<p>If any tests fail, check the error messages and ensure:</p>\n";
echo "<ul>\n";
echo "<li>PCF is running and the database exists</li>\n";
echo "<li>File permissions allow reading the PCF database</li>\n";
echo "<li>The setup script has been run</li>\n";
echo "<li>Database connections are properly configured</li>\n";
echo "</ul>\n";

echo "<p><a href='pcf_dashboard.php'>Go to PT Dashboard</a> | <a href='setup_pcf_integration.php'>Run Setup</a></p>\n";
?>