<?php
/**
 * Test script for password validation
 * 
 * This script tests the password validation function to ensure it properly
 * validates passwords according to the requirements.
 */

require_once 'includes/functions.php';

echo "<h1>Password Validation Test</h1>";
echo "<p>Testing password validation with various inputs...</p>";

$testPasswords = [
    'weak' => 'Should fail - too short',
    'NoNumbers!' => 'Should fail - no numbers',
    'nonumbers1!' => 'Should fail - no uppercase',
    'NOLOWERCASE1!' => 'Should fail - no lowercase',
    'NoSymbols123' => 'Should fail - no symbols',
    'Valid1!' => 'Should pass - meets all requirements',
    'MyP@ssw0rd' => 'Should pass - meets all requirements',
    'Str0ng!Pass' => 'Should pass - meets all requirements',
    'Test123!' => 'Should pass - meets all requirements',
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Password</th>";
echo "<th>Expected Result</th>";
echo "<th>Actual Result</th>";
echo "<th>Error Message</th>";
echo "</tr>";

foreach ($testPasswords as $password => $expected) {
    $result = validatePassword($password);
    $status = $result['valid'] ? 'PASS ✓' : 'FAIL ✗';
    $statusColor = $result['valid'] ? '#d4edda' : '#f8d7da';
    
    echo "<tr style='background-color: $statusColor;'>";
    echo "<td><code>" . htmlspecialchars($password) . "</code></td>";
    echo "<td>" . htmlspecialchars($expected) . "</td>";
    echo "<td><strong>" . htmlspecialchars($status) . "</strong></td>";
    echo "<td>" . htmlspecialchars($result['error']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2 style='margin-top: 30px;'>Password Requirements Summary:</h2>";
echo "<ul>";
echo "<li>Minimum 7 characters</li>";
echo "<li>At least one uppercase letter (A-Z)</li>";
echo "<li>At least one lowercase letter (a-z)</li>";
echo "<li>At least one number (0-9)</li>";
echo "<li>At least one symbol/special character (!@#$%^&* etc.)</li>";
echo "</ul>";

echo "<p style='margin-top: 20px;'><a href='profile.php'>Go to Profile Page</a> | <a href='admin.php'>Go to Admin Page</a></p>";
?>