<?php
/**
 * PCF Integration Installer
 * 
 * This script installs and configures the PCF integration for AMT
 */

// Prevent direct access from web
if (php_sapi_name() !== 'cli' && !isset($_GET['web_install'])) {
    echo "<h1>PCF Integration Installer</h1>";
    echo "<p>This installer will set up the PCF integration for your AMT.</p>";
    echo "<p><strong>Warning:</strong> This will create new database tables and sync data from PCF.</p>";
    echo "<p><a href='?web_install=1' class='btn btn-primary' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Start Installation</a></p>";
    echo "<style>body { font-family: Arial, sans-serif; margin: 40px; }</style>";
    exit;
}

require_once 'config/database.php';
require_once 'includes/pcf_functions.php';
require_once 'includes/pcf_remote_functions.php';

echo "<h1>PCF Integration Installation</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; font-weight: bold; }
    .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
</style>\n";

$errors = [];
$warnings = [];

// Step 1: Check Prerequisites
echo "<div class='step'>\n";
echo "<h2>Step 1: Checking Prerequisites</h2>\n";

// Check CTI database connection
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='success'>✓ CTI database connection successful</p>\n";
} catch (Exception $e) {
    $errors[] = "CTI database connection failed: " . $e->getMessage();
    echo "<p class='error'>✗ CTI database connection failed</p>\n";
}

// Check PCF database
$pcfPdo = getPcfRemoteConnection();
if ($pcfPdo) {
    echo "<p class='success'>✓ PCF database connection successful</p>\n";
    
    // Get PCF stats
    try {
        $stmt = $pcfPdo->query("SELECT COUNT(*) as count FROM Issues");
        $issueCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pcfPdo->query("SELECT COUNT(*) as count FROM Projects");
        $projectCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p class='info'>Found {$issueCount} issues and {$projectCount} projects in PCF</p>\n";
        
        if ($issueCount == 0) {
            $warnings[] = "No issues found in PCF database";
        }
        
    } catch (Exception $e) {
        $warnings[] = "Could not query PCF database: " . $e->getMessage();
    }
} else {
    $errors[] = "Cannot connect to PCF database";
    echo "<p class='error'>✗ PCF database connection failed</p>\n";
}

echo "</div>\n";

// Step 2: Create Tables
echo "<div class='step'>\n";
echo "<h2>Step 2: Creating Database Tables</h2>\n";

if (empty($errors)) {
    try {
        createPcfFindingsTable($pdo);
        echo "<p class='success'>✓ PCF integration tables created successfully</p>\n";
    } catch (Exception $e) {
        $errors[] = "Failed to create tables: " . $e->getMessage();
        echo "<p class='error'>✗ Failed to create tables: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    echo "<p class='error'>✗ Skipping table creation due to previous errors</p>\n";
}

echo "</div>\n";

// Step 3: Initial Sync
echo "<div class='step'>\n";
echo "<h2>Step 3: Initial Data Sync</h2>\n";

if (empty($errors)) {
    try {
        echo "<p class='info'>Starting initial sync from PCF...</p>\n";
        $syncResult = syncRemotePcfFindings($pdo);
        
        if ($syncResult['success']) {
            echo "<p class='success'>✓ Initial sync completed successfully</p>\n";
            echo "<p class='info'>Synced {$syncResult['count']} findings from PCF</p>\n";
        } else {
            $errors[] = "Initial sync failed: " . $syncResult['error'];
            echo "<p class='error'>✗ Initial sync failed: " . htmlspecialchars($syncResult['error']) . "</p>\n";
        }
    } catch (Exception $e) {
        $errors[] = "Sync error: " . $e->getMessage();
        echo "<p class='error'>✗ Sync error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    echo "<p class='error'>✗ Skipping initial sync due to previous errors</p>\n";
}

echo "</div>\n";

// Step 4: Verify Installation
echo "<div class='step'>\n";
echo "<h2>Step 4: Verifying Installation</h2>\n";

if (empty($errors)) {
    try {
        // Check tables exist and have data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM pcf_findings");
        $findingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM pcf_sync_log");
        $syncCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p class='success'>✓ Installation verification successful</p>\n";
        echo "<p class='info'>PCF findings in database: {$findingCount}</p>\n";
        echo "<p class='info'>Sync log entries: {$syncCount}</p>\n";
        
        if ($findingCount > 0) {
            // Show sample findings
            $stmt = $pdo->query("SELECT name, cvss, status, project_name FROM pcf_findings ORDER BY cvss DESC LIMIT 5");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p class='info'>Sample findings:</p>\n";
            echo "<ul>\n";
            foreach ($samples as $sample) {
                echo "<li>" . htmlspecialchars($sample['name']) . " (CVSS: {$sample['cvss']}, Project: " . htmlspecialchars($sample['project_name']) . ")</li>\n";
            }
            echo "</ul>\n";
        }
        
    } catch (Exception $e) {
        $errors[] = "Verification failed: " . $e->getMessage();
        echo "<p class='error'>✗ Verification failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    echo "<p class='error'>✗ Skipping verification due to previous errors</p>\n";
}

echo "</div>\n";

// Installation Summary
echo "<div class='step'>\n";
echo "<h2>Installation Summary</h2>\n";

if (empty($errors)) {
    echo "<p class='success'>🎉 PCF Integration installed successfully!</p>\n";
    echo "<h3>What's Next:</h3>\n";
    echo "<ul>\n";
    echo "<li><a href='pcf_dashboard.php'>Access the PT Dashboard</a></li>\n";
    echo "<li><a href='test_pcf_integration.php'>Run integration tests</a></li>\n";
    echo "<li>Set up automatic sync (see README for cron instructions)</li>\n";
    echo "</ul>\n";
    
    echo "<h3>Features Available:</h3>\n";
    echo "<ul>\n";
    echo "<li>View all PCF findings with severity color-coding</li>\n";
    echo "<li>Filter by project, severity, and status</li>\n";
    echo "<li>Detailed finding views with technical information</li>\n";
    echo "<li>Manual sync capability</li>\n";
    echo "<li>Statistics dashboard</li>\n";
    echo "</ul>\n";
    
} else {
    echo "<p class='error'>❌ Installation failed with errors:</p>\n";
    echo "<ul>\n";
    foreach ($errors as $error) {
        echo "<li class='error'>" . htmlspecialchars($error) . "</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h3>Troubleshooting:</h3>\n";
    echo "<ul>\n";
    echo "<li>Ensure PCF is running and accessible</li>\n";
    echo "<li>Check database permissions and connectivity</li>\n";
    echo "<li>Verify file paths in pcf_functions.php</li>\n";
    echo "<li>Run the test script for detailed diagnostics</li>\n";
    echo "</ul>\n";
}

if (!empty($warnings)) {
    echo "<h3>Warnings:</h3>\n";
    echo "<ul>\n";
    foreach ($warnings as $warning) {
        echo "<li class='warning'>" . htmlspecialchars($warning) . "</li>\n";
    }
    echo "</ul>\n";
}

echo "</div>\n";

echo "<p><a href='index.php'>Return to Dashboard</a></p>\n";
?>