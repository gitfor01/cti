<?php
/**
 * Test script to verify the warning functionality
 */

require_once 'includes/config.php';
require_once 'includes/pcf_functions.php';

echo "Testing PCF Warning Functionality\n";
echo "=================================\n\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test sync
    echo "1. Testing PCF sync...\n";
    $syncResult = syncPcfFindings($pdo);
    if ($syncResult['success']) {
        echo "   ✓ Sync successful: {$syncResult['count']} findings synced\n";
    } else {
        echo "   ✗ Sync failed: {$syncResult['error']}\n";
        exit(1);
    }
    
    // Test warning count
    echo "\n2. Testing warning count...\n";
    $warningCount = getWarningFindingsCount($pdo);
    echo "   Warning findings count: $warningCount\n";
    
    // Test warning findings
    echo "\n3. Testing warning findings details...\n";
    $warningFindings = getWarningFindings($pdo);
    
    if (empty($warningFindings)) {
        echo "   No warning findings found.\n";
        echo "   This could mean:\n";
        echo "   - No high/critical findings exist\n";
        echo "   - All findings are from recent projects\n";
        echo "   - All findings have been marked as risk raised\n";
    } else {
        echo "   Found {$warningCount} warning findings:\n\n";
        
        foreach ($warningFindings as $finding) {
            echo "   • {$finding['name']}\n";
            echo "     Project: {$finding['project_name']}\n";
            echo "     CVSS: {$finding['cvss']}\n";
            echo "     Status: {$finding['status']}\n";
            echo "     Age: {$finding['days_old']} days ({$finding['age_reason']})\n";
            echo "     Created: {$finding['created_at']}\n";
            if ($finding['end_date']) {
                echo "     Project ended: {$finding['end_date']}\n";
            }
            echo "\n";
        }
    }
    
    // Show all findings for debugging
    echo "\n4. All PCF findings (for debugging)...\n";
    $stmt = $pdo->prepare("SELECT name, project_name, cvss, status, created_at, end_date FROM pcf_findings ORDER BY cvss DESC");
    $stmt->execute();
    $allFindings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allFindings as $finding) {
        echo "   • {$finding['name']} (CVSS: {$finding['cvss']}, Status: {$finding['status']})\n";
        echo "     Project: {$finding['project_name']}\n";
        echo "     Created: {$finding['created_at']}\n";
        if ($finding['end_date']) {
            echo "     Project ended: {$finding['end_date']}\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Test completed!\n";
?>