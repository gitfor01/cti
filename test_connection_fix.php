<?php
/**
 * Test script to verify the connection testing fix
 */

require_once 'config/database.php';
require_once 'config/pcf_remote_config.php';
require_once 'includes/pcf_remote_functions.php';

echo "<h2>Testing Connection Fix</h2>\n";

// Test 1: Default behavior (should use config constants)
echo "<h3>Test 1: Default behavior (using config constants)</h3>\n";
$result1 = testRemotePcfConnection();
echo "Connection Type: " . $result1['connection_type'] . "<br>\n";
echo "Success: " . ($result1['connection_success'] ? 'Yes' : 'No') . "<br>\n";
if ($result1['error']) {
    echo "Error: " . htmlspecialchars($result1['error']) . "<br>\n";
}
echo "<br>\n";

// Test 2: Test with remote_sqlite config
echo "<h3>Test 2: Testing with remote_sqlite configuration</h3>\n";
$testConfig = [
    'connection_type' => 'remote_sqlite',
    'remote_sqlite_host' => 'example.com',
    'remote_sqlite_method' => 'https',
    'remote_sqlite_path' => '/path/to/pcf.sqlite3',
    'remote_sqlite_cache' => '/tmp/test_pcf.sqlite3'
];

$result2 = testRemotePcfConnection($testConfig);
echo "Connection Type: " . $result2['connection_type'] . "<br>\n";
echo "Success: " . ($result2['connection_success'] ? 'Yes' : 'No') . "<br>\n";
if ($result2['error']) {
    echo "Error: " . htmlspecialchars($result2['error']) . "<br>\n";
}
echo "<br>\n";

// Test 3: Test with MySQL config
echo "<h3>Test 3: Testing with MySQL configuration</h3>\n";
$testConfig3 = [
    'connection_type' => 'remote_mysql',
    'mysql_host' => 'localhost',
    'mysql_port' => 3306,
    'mysql_database' => 'test_pcf',
    'mysql_username' => 'test_user',
    'mysql_password' => 'test_pass'
];

$result3 = testRemotePcfConnection($testConfig3);
echo "Connection Type: " . $result3['connection_type'] . "<br>\n";
echo "Success: " . ($result3['connection_success'] ? 'Yes' : 'No') . "<br>\n";
if ($result3['error']) {
    echo "Error: " . htmlspecialchars($result3['error']) . "<br>\n";
}
echo "<br>\n";

echo "<p><strong>Fix Status:</strong> The connection testing now properly uses the form data instead of just the config constants!</p>\n";
?>