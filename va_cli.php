<?php
/**
 * Tenable Security Center - Monthly Vulnerability Tracker (CLI Version)
 * Tracks new vulnerabilities introduced and vulnerabilities closed each month
 * Command-line interface for the VA Dashboard
 */

// ============================================
// CONFIGURATION - Update these values
// ============================================
$SC_HOST = 'https://your-sc-instance.com';  // Your Tenable SC hostname
$ACCESS_KEY = 'YOUR_ACCESS_KEY_HERE';        // Your API Access Key
$SECRET_KEY = 'YOUR_SECRET_KEY_HERE';        // Your API Secret Key
$MONTHS_TO_ANALYZE = 12;                     // Number of months to analyze

// ============================================
// DO NOT MODIFY BELOW THIS LINE
// ============================================

class TenableSCAPI {
    private $host;
    private $accessKey;
    private $secretKey;
    
    public function __construct($host, $accessKey, $secretKey) {
        $this->host = rtrim($host, '/');
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    
    /**
     * Make API request to Tenable SC
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->host . '/rest' . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to true in production with proper cert
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $headers = [
            'x-apikey: accesskey=' . $this->accessKey . '; secretkey=' . $this->secretKey . ';',
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API request failed with status code: $httpCode\nResponse: $response");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get vulnerability count for a specific time range and status
     */
    public function getVulnerabilityCount($startTime, $endTime, $filters = []) {
        $queryFilters = [
            [
                'filterName' => 'lastSeen',
                'operator' => '=',
                'value' => $endTime . ':' . $startTime
            ]
        ];
        
        // Add additional filters if provided
        foreach ($filters as $filter) {
            $queryFilters[] = $filter;
        }
        
        $requestData = [
            'type' => 'vuln',
            'sourceType' => 'cumulative',
            'query' => [
                'tool' => 'listvuln',
                'type' => 'vuln',
                'filters' => $queryFilters
            ],
            'startOffset' => 0,
            'endOffset' => 1
        ];
        
        $response = $this->makeRequest('/analysis', 'POST', $requestData);
        
        if (isset($response['response']['totalRecords'])) {
            return (int)$response['response']['totalRecords'];
        }
        
        return 0;
    }
    
    /**
     * Get vulnerabilities first seen in a date range (new vulnerabilities)
     */
    public function getNewVulnerabilities($startTime, $endTime) {
        $queryFilters = [
            [
                'filterName' => 'firstSeen',
                'operator' => '=',
                'value' => $startTime . '-' . $endTime
            ]
        ];
        
        $requestData = [
            'type' => 'vuln',
            'sourceType' => 'cumulative',
            'query' => [
                'tool' => 'listvuln',
                'type' => 'vuln',
                'filters' => $queryFilters
            ],
            'startOffset' => 0,
            'endOffset' => 1
        ];
        
        $response = $this->makeRequest('/analysis', 'POST', $requestData);
        
        if (isset($response['response']['totalRecords'])) {
            return (int)$response['response']['totalRecords'];
        }
        
        return 0;
    }
    
    /**
     * Get vulnerabilities that were mitigated/closed in a date range
     */
    public function getClosedVulnerabilities($startTime, $endTime) {
        $queryFilters = [
            [
                'filterName' => 'lastMitigated',
                'operator' => '=',
                'value' => $startTime . '-' . $endTime
            ]
        ];
        
        $requestData = [
            'type' => 'vuln',
            'sourceType' => 'patched',
            'query' => [
                'tool' => 'listvuln',
                'type' => 'vuln',
                'filters' => $queryFilters
            ],
            'startOffset' => 0,
            'endOffset' => 1
        ];
        
        $response = $this->makeRequest('/analysis', 'POST', $requestData);
        
        if (isset($response['response']['totalRecords'])) {
            return (int)$response['response']['totalRecords'];
        }
        
        return 0;
    }
}

/**
 * Format timestamp to epoch time for Tenable SC API
 */
function getMonthTimestamps($monthsAgo) {
    $date = new DateTime();
    $date->modify("-$monthsAgo months");
    $date->modify('first day of this month');
    $date->setTime(0, 0, 0);
    $startTime = $date->getTimestamp();
    
    $date->modify('last day of this month');
    $date->setTime(23, 59, 59);
    $endTime = $date->getTimestamp();
    
    return [$startTime, $endTime];
}

/**
 * Display colored output in terminal
 */
function colorOutput($text, $color = 'white') {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'magenta' => "\033[35m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
        'reset' => "\033[0m"
    ];
    
    return $colors[$color] . $text . $colors['reset'];
}

/**
 * Main execution
 */
try {
    echo colorOutput("Tenable Security Center - Monthly Vulnerability Analysis\n", 'cyan');
    echo colorOutput(str_repeat("=", 80) . "\n\n", 'blue');
    
    // Check if running from command line
    if (php_sapi_name() !== 'cli') {
        echo colorOutput("This script should be run from the command line.\n", 'red');
        echo colorOutput("For web interface, use va_dashboard.php\n", 'yellow');
        exit(1);
    }
    
    // Validate configuration
    if ($ACCESS_KEY === 'YOUR_ACCESS_KEY_HERE' || $SECRET_KEY === 'YOUR_SECRET_KEY_HERE') {
        echo colorOutput("ERROR: Please configure your API keys in the script before running.\n", 'red');
        echo colorOutput("Edit the configuration section at the top of this file.\n", 'yellow');
        exit(1);
    }
    
    // Initialize API client
    echo colorOutput("Connecting to Tenable SC at: $SC_HOST\n", 'yellow');
    $api = new TenableSCAPI($SC_HOST, $ACCESS_KEY, $SECRET_KEY);
    
    echo colorOutput(sprintf("%-15s %-20s %-20s %-15s\n", 
        "Month", "New Vulnerabilities", "Closed Vulnerabilities", "Net Change"), 'cyan');
    echo colorOutput(str_repeat("-", 80) . "\n", 'blue');
    
    $results = [];
    
    // Analyze each month
    for ($i = $MONTHS_TO_ANALYZE - 1; $i >= 0; $i--) {
        list($startTime, $endTime) = getMonthTimestamps($i);
        
        $monthName = date('Y-m', $startTime);
        
        echo colorOutput("Processing $monthName... ", 'yellow');
        flush();
        
        // Get new vulnerabilities introduced in this month
        $newVulns = $api->getNewVulnerabilities($startTime, $endTime);
        
        // Get vulnerabilities closed/mitigated in this month
        $closedVulns = $api->getClosedVulnerabilities($startTime, $endTime);
        
        // Calculate net change
        $netChange = $newVulns - $closedVulns;
        $changeIndicator = $netChange > 0 ? "+$netChange" : "$netChange";
        
        $results[] = [
            'month' => $monthName,
            'new' => $newVulns,
            'closed' => $closedVulns,
            'net' => $netChange
        ];
        
        // Color code the net change
        $netColor = $netChange > 0 ? 'red' : ($netChange < 0 ? 'green' : 'white');
        
        echo sprintf("%-20s %-20s %s\n", 
            $newVulns, 
            $closedVulns, 
            colorOutput(sprintf("%-15s", $changeIndicator), $netColor)
        );
    }
    
    echo colorOutput(str_repeat("-", 80) . "\n", 'blue');
    
    // Calculate totals
    $totalNew = array_sum(array_column($results, 'new'));
    $totalClosed = array_sum(array_column($results, 'closed'));
    $totalNet = $totalNew - $totalClosed;
    $totalNetIndicator = $totalNet > 0 ? "+$totalNet" : "$totalNet";
    $totalNetColor = $totalNet > 0 ? 'red' : ($totalNet < 0 ? 'green' : 'white');
    
    echo sprintf("%-15s %-20s %-20s %s\n", 
        colorOutput("TOTAL", 'cyan'), 
        colorOutput($totalNew, 'white'), 
        colorOutput($totalClosed, 'white'), 
        colorOutput(sprintf("%-15s", $totalNetIndicator), $totalNetColor)
    );
    
    echo "\n" . colorOutput(str_repeat("=", 80) . "\n", 'blue');
    echo colorOutput("Analysis complete!\n\n", 'green');
    
    // Summary
    echo colorOutput("SUMMARY:\n", 'cyan');
    echo colorOutput("- Total new vulnerabilities: $totalNew\n", 'white');
    echo colorOutput("- Total closed vulnerabilities: $totalClosed\n", 'white');
    echo colorOutput("- Net change: ", 'white') . colorOutput("$totalNetIndicator\n", $totalNetColor);
    
    if ($totalNet > 0) {
        echo colorOutput("- Status: ⚠️  Vulnerability backlog is growing\n", 'red');
    } elseif ($totalNet < 0) {
        echo colorOutput("- Status: ✓ Vulnerability backlog is decreasing\n", 'green');
    } else {
        echo colorOutput("- Status: → Vulnerability backlog is stable\n", 'yellow');
    }
    
    echo "\n" . colorOutput("💡 Tip: Use the web interface at va_dashboard.php for interactive visualizations!\n", 'cyan');
    
} catch (Exception $e) {
    echo "\n\n" . colorOutput("ERROR: " . $e->getMessage() . "\n", 'red');
    exit(1);
}
?>