<?php
/**
 * Verification Script for Tenable Integration Fixes
 * This script verifies that all fixes have been applied correctly
 */

echo "=== Tenable Integration Fixes Verification ===\n\n";

$allPassed = true;

// Test 1: Check test_tenable_integration.php uses 'method' not 'method_used'
echo "Test 1: Checking test_tenable_integration.php for correct key name...\n";
$testContent = file_get_contents(__DIR__ . '/test_tenable_integration.php');
if (strpos($testContent, "\$method = \$result['method']") !== false) {
    echo "✅ PASS: test_tenable_integration.php uses correct 'method' key\n";
} else {
    echo "❌ FAIL: test_tenable_integration.php still uses incorrect key\n";
    $allPassed = false;
}

if (strpos($testContent, "method_used") !== false) {
    echo "❌ FAIL: test_tenable_integration.php still contains 'method_used' reference\n";
    $allPassed = false;
} else {
    echo "✅ PASS: No 'method_used' references found\n";
}
echo "\n";

// Test 2: Check va_api.php has progress callback in tryGetAssetInstancesFromSumid
echo "Test 2: Checking va_api.php for progress callback parameter...\n";
$apiContent = file_get_contents(__DIR__ . '/va_api.php');
if (strpos($apiContent, 'private function tryGetAssetInstancesFromSumid($queryFilters, $progressCallback = null)') !== false) {
    echo "✅ PASS: tryGetAssetInstancesFromSumid() has progress callback parameter\n";
} else {
    echo "❌ FAIL: tryGetAssetInstancesFromSumid() missing progress callback parameter\n";
    $allPassed = false;
}
echo "\n";

// Test 3: Check va_api.php passes callback to tryGetAssetInstancesFromSumid
echo "Test 3: Checking va_api.php passes callback to Method 1...\n";
if (strpos($apiContent, '$sumidResult = $this->tryGetAssetInstancesFromSumid($queryFilters, $progressCallback)') !== false) {
    echo "✅ PASS: Callback is passed to tryGetAssetInstancesFromSumid()\n";
} else {
    echo "❌ FAIL: Callback not passed to tryGetAssetInstancesFromSumid()\n";
    $allPassed = false;
}
echo "\n";

// Test 4: Check va_api.php has progress reporting in Method 1
echo "Test 4: Checking va_api.php for progress reporting in Method 1...\n";
if (strpos($apiContent, 'call_user_func($progressCallback, "Method 1: fetching page "') !== false) {
    echo "✅ PASS: Method 1 has progress reporting during pagination\n";
} else {
    echo "❌ FAIL: Method 1 missing progress reporting\n";
    $allPassed = false;
}
echo "\n";

// Test 5: Verify all three methods return 'method' key
echo "Test 5: Checking va_api.php returns 'method' key in all cases...\n";
$methodReturns = [
    "['method' => 'sumid_count_field']" => false,
    "['method' => 'bulk_export']" => false,
    "['method' => 'individual_queries']" => false
];

foreach ($methodReturns as $pattern => $found) {
    if (strpos($apiContent, $pattern) !== false) {
        $methodReturns[$pattern] = true;
        echo "✅ PASS: Found return with $pattern\n";
    } else {
        echo "❌ FAIL: Missing return with $pattern\n";
        $allPassed = false;
    }
}
echo "\n";

// Test 6: Check dashboard expects 'method' key
echo "Test 6: Checking va_api.php dashboard code expects 'method' key...\n";
if (strpos($apiContent, "\$monthData['current'][\$sev]['method']") !== false) {
    echo "✅ PASS: Dashboard code expects 'method' key\n";
} else {
    echo "❌ FAIL: Dashboard code doesn't expect 'method' key\n";
    $allPassed = false;
}
echo "\n";

// Final Summary
echo "=== VERIFICATION SUMMARY ===\n";
if ($allPassed) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "All fixes have been applied correctly.\n";
    echo "The integration is ready for testing.\n";
} else {
    echo "❌ SOME TESTS FAILED!\n";
    echo "Please review the failed tests above.\n";
}
echo "\n";

exit($allPassed ? 0 : 1);
?>