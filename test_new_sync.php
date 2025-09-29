<?php
/**
 * Test script for the new PCF sync functionality
 * This script tests the improved sync logic that preserves status changes
 */

require_once 'config/database.php';
require_once 'includes/pcf_functions.php';

echo "=== Testing New PCF Sync Logic ===\n\n";

try {
    // Test 1: Initial sync
    echo "1. Testing initial sync...\n";
    $result = syncPcfFindings($pdo);
    
    if ($result['success']) {
        echo "✓ Initial sync successful!\n";
        echo "  - Inserted: {$result['inserted']} findings\n";
        echo "  - Updated: {$result['updated']} findings\n";
        echo "  - Deleted: {$result['deleted']} findings\n";
        echo "  - Total processed: {$result['total_processed']} findings\n\n";
    } else {
        echo "✗ Initial sync failed: {$result['error']}\n\n";
        exit(1);
    }
    
    // Test 2: Get a finding and change its status
    echo "2. Testing status preservation...\n";
    $stmt = $pdo->prepare("SELECT id, pcf_id, name, status FROM pcf_findings LIMIT 1");
    $stmt->execute();
    $finding = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($finding) {
        $originalStatus = $finding['status'];
        $newStatus = ($originalStatus === 'open') ? 'closed' : 'open';
        
        echo "  - Found finding: {$finding['name']} (PCF ID: {$finding['pcf_id']})\n";
        echo "  - Original status: {$originalStatus}\n";
        echo "  - Changing status to: {$newStatus}\n";
        
        // Update the status
        $updateStmt = $pdo->prepare("UPDATE pcf_findings SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $finding['id']]);
        
        echo "  - Status updated successfully\n\n";
        
        // Test 3: Sync again and check if status is preserved
        echo "3. Testing sync with status preservation...\n";
        $result2 = syncPcfFindings($pdo);
        
        if ($result2['success']) {
            echo "✓ Second sync successful!\n";
            echo "  - Inserted: {$result2['inserted']} findings\n";
            echo "  - Updated: {$result2['updated']} findings\n";
            echo "  - Deleted: {$result2['deleted']} findings\n\n";
            
            // Check if status was preserved
            $checkStmt = $pdo->prepare("SELECT status FROM pcf_findings WHERE id = ?");
            $checkStmt->execute([$finding['id']]);
            $currentStatus = $checkStmt->fetchColumn();
            
            if ($currentStatus === $newStatus) {
                echo "✓ Status preservation test PASSED!\n";
                echo "  - Status remained: {$currentStatus}\n\n";
            } else {
                echo "✗ Status preservation test FAILED!\n";
                echo "  - Expected: {$newStatus}\n";
                echo "  - Got: {$currentStatus}\n\n";
            }
        } else {
            echo "✗ Second sync failed: {$result2['error']}\n\n";
        }
    } else {
        echo "  - No findings found to test status preservation\n\n";
    }
    
    echo "=== Test completed ===\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with exception: " . $e->getMessage() . "\n";
    exit(1);
}
?>