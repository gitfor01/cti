<?php
/**
 * Simple Test to Check Server Configuration
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Server Test</title></head><body>";
echo "<h1>Server Status: OK</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
} else {
    echo "<h2>Test Form:</h2>";
    echo "<form method='POST' action=''>";
    echo "<input type='text' name='test' value='Hello' />";
    echo "<button type='submit'>Submit Test</button>";
    echo "</form>";
}

echo "</body></html>";
?>