<?php
/**
 * PCF Integration Setup Script
 * 
 * This script sets up the necessary tables for PCF integration
 * Run this once to initialize the PCF dashboard functionality
 */

require_once 'config/database.php';
require_once 'includes/pcf_functions.php';
require_once 'includes/pcf_remote_functions.php';

echo "<h1>PCF Integration Setup</h1>\n";

try {
    // Create PCF findings table
    echo "<p>Creating PCF findings table...</p>\n";
    createPcfFindingsTable($pdo);
    echo "<p style='color: green;'>✓ PCF findings table created successfully</p>\n";
    
    // Test PCF connection
    echo "<p>Testing PCF database connection...</p>\n";
    $pcfPdo = getPcfRemoteConnection();
    if ($pcfPdo) {
        echo "<p style='color: green;'>✓ PCF database connection successful</p>\n";
        
        // Get PCF database info
        $stmt = $pcfPdo->query("SELECT COUNT(*) as issue_count FROM Issues");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Found {$result['issue_count']} issues in PCF database</p>\n";
        
        $stmt = $pcfPdo->query("SELECT COUNT(*) as project_count FROM Projects");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Found {$result['project_count']} projects in PCF database</p>\n";
        
    } else {
        echo "<p style='color: red;'>✗ Could not connect to PCF database</p>\n";
        echo "<p>Please ensure PCF is running and the database path is correct in pcf_functions.php</p>\n";
    }
    
    // Perform initial sync
    echo "<p>Performing initial sync...</p>\n";
    $syncResult = syncRemotePcfFindings($pdo);
    
    if ($syncResult['success']) {
        echo "<p style='color: green;'>✓ Initial sync completed successfully</p>\n";
        echo "<p>Synced {$syncResult['count']} findings from PCF</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Initial sync failed: {$syncResult['error']}</p>\n";
    }
    
    echo "<h2>Setup Complete!</h2>\n";
    echo "<p>You can now access the PT Dashboard from the navigation menu.</p>\n";
    echo "<p><a href='pcf_dashboard.php'>Go to PT Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Setup failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1 { color: #333; }
h2 { color: #666; }
p { margin: 10px 0; }
</style>