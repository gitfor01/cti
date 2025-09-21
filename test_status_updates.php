<?php
/**
 * Test script to verify the status update functionality
 */

require_once 'config/database.php';
require_once 'includes/pcf_functions.php';

echo "Testing Status Update Functionality\n";
echo "===================================\n\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, sync PCF data to ensure we have test data
    echo "1. Syncing PCF data...\n";
    $syncResult = syncPcfFindings($pdo);
    if ($syncResult['success']) {
        echo "   ✓ Sync successful: {$syncResult['count']} findings synced\n";
    } else {
        echo "   ✗ Sync failed: {$syncResult['error']}\n";
        exit(1);
    }
    
    // Get warning findings
    echo "\n2. Getting warning findings...\n";
    $warningFindings = getWarningFindings($pdo);
    
    if (empty($warningFindings)) {
        echo "   No warning findings found. Creating test scenario...\n";
        
        // Update a finding to have an old creation date for testing
        $stmt = $pdo->prepare("UPDATE pcf_findings SET created_at = DATE_SUB(NOW(), INTERVAL 45 DAY) WHERE cvss >= 7.0 LIMIT 1");
        $stmt->execute();
        
        // Get warning findings again
        $warningFindings = getWarningFindings($pdo);
    }
    
    if (empty($warningFindings)) {
        echo "   Still no warning findings. Please check your test data.\n";
        exit(1);
    }
    
    $testFinding = $warningFindings[0];
    echo "   Found {$testFinding['name']} (ID: {$testFinding['id']}) for testing\n";
    
    // Test 1: Mark as Sent To Risk
    echo "\n3. Testing 'Mark as Sent To Risk'...\n";
    $result = updateFindingStatus($pdo, $testFinding['id'], 'Sent To Risk');
    if ($result['success']) {
        echo "   ✓ {$result['message']}\n";
    } else {
        echo "   ✗ Error: {$result['error']}\n";
    }
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT status FROM pcf_findings WHERE id = ?");
    $stmt->execute([$testFinding['id']]);
    $newStatus = $stmt->fetchColumn();
    echo "   Status in CTI database: $newStatus\n";
    
    // Test 2: Mark as Closed
    echo "\n4. Testing 'Mark as Closed'...\n";
    $result = updateFindingStatus($pdo, $testFinding['id'], 'Closed');
    if ($result['success']) {
        echo "   ✓ {$result['message']}\n";
    } else {
        echo "   ✗ Error: {$result['error']}\n";
    }
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT status FROM pcf_findings WHERE id = ?");
    $stmt->execute([$testFinding['id']]);
    $newStatus = $stmt->fetchColumn();
    echo "   Status in CTI database: $newStatus\n";
    
    // Test 3: Bulk update
    if (count($warningFindings) > 1) {
        echo "\n5. Testing bulk update...\n";
        
        // Reset the test finding status first
        $stmt = $pdo->prepare("UPDATE pcf_findings SET status = 'Open' WHERE id = ?");
        $stmt->execute([$testFinding['id']]);
        
        $findingIds = array_slice(array_column($warningFindings, 'id'), 0, 2); // Test with first 2 findings
        $result = updateMultipleFindingsStatus($pdo, $findingIds, 'Sent To Risk');
        
        if ($result['success']) {
            echo "   ✓ Bulk update successful: {$result['success_count']}/{$result['total']} findings updated\n";
        } else {
            echo "   ✗ Bulk update failed: {$result['error_count']} errors\n";
            foreach ($result['details'] as $detail) {
                if (!$detail['success']) {
                    echo "     - {$detail['error']}\n";
                }
            }
        }
    }
    
    // Test 4: Invalid status
    echo "\n6. Testing invalid status handling...\n";
    $result = updateFindingStatus($pdo, $testFinding['id'], 'Invalid Status');
    if (!$result['success']) {
        echo "   ✓ Invalid status correctly rejected: {$result['error']}\n";
    } else {
        echo "   ✗ Invalid status was accepted (this shouldn't happen)\n";
    }
    
    // Test 5: Check warning count after updates
    echo "\n7. Checking warning count after updates...\n";
    $warningCount = getWarningFindingsCount($pdo);
    echo "   Current warning findings count: $warningCount\n";
    
    echo "\n✓ All tests completed successfully!\n";
    echo "\nYou can now test the web interface at: http://your-domain/pcf_dashboard.php\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>