<?php
/**
 * VA Dashboard API - Tenable Security Center Integration
 * Handles vulnerability data retrieval and analysis
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session for authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$requiredFields = ['scHost', 'accessKey', 'secretKey', 'monthsToAnalyze'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $api = new TenableSCAPI($input['scHost'], $input['accessKey'], $input['secretKey']);
    $monthsToAnalyze = (int)$input['monthsToAnalyze'];
    
    $results = [];
    
    // Analyze each month
    for ($i = $monthsToAnalyze - 1; $i >= 0; $i--) {
        list($startTime, $endTime) = getMonthTimestamps($i);
        $monthName = date('Y-m', $startTime);
        
        // Get comprehensive vulnerability data with severity breakdown
        $monthData = $api->getMonthlyVulnerabilityData($startTime, $endTime);
        
        // Calculate net change
        $netChange = $monthData['totals']['new'] - $monthData['totals']['closed'];
        
        $results[] = [
            'month' => $monthName,
            'new' => $monthData['totals']['new'],
            'closed' => $monthData['totals']['closed'],
            'net' => $netChange,
            'severity' => [
                'new' => $monthData['new'],
                'closed' => $monthData['closed']
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'summary' => [
            'totalNew' => array_sum(array_column($results, 'new')),
            'totalClosed' => array_sum(array_column($results, 'closed')),
            'totalNet' => array_sum(array_column($results, 'net'))
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Tenable Security Center API Client
 */
class TenableSCAPI {
    private $host;
    private $accessKey;
    private $secretKey;
    
    // Severity mappings
    const SEVERITY_CRITICAL = '4';
    const SEVERITY_HIGH = '3';
    const SEVERITY_MEDIUM = '2';
    const SEVERITY_LOW = '1';
    const SEVERITY_INFO = '0';
    
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
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Connection error: ' . $error);
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API request failed with status code: $httpCode. Response: $response");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        return $decoded;
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
    
    /**
     * Get vulnerability count for a specific time range and status
     */
    public function getVulnerabilityCount($startTime, $endTime, $filters = []) {
        $queryFilters = [
            [
                'filterName' => 'lastSeen',
                'operator' => '=',
                'value' => $startTime . '-' . $endTime
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
                'tool' => 'sumid',
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
     * Get new vulnerabilities by severity for a specific time range
     */
    public function getNewVulnerabilitiesBySeverity($startTime, $endTime, $severity) {
        $queryFilters = [
            [
                'filterName' => 'firstSeen',
                'operator' => '=',
                'value' => $startTime . '-' . $endTime
            ],
            [
                'filterName' => 'severity',
                'operator' => '=',
                'value' => $severity
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
     * Get closed vulnerabilities by severity for a specific time range
     */
    public function getClosedVulnerabilitiesBySeverity($startTime, $endTime, $severity) {
        $queryFilters = [
            [
                'filterName' => 'lastMitigated',
                'operator' => '=',
                'value' => $startTime . '-' . $endTime
            ],
            [
                'filterName' => 'severity',
                'operator' => '=',
                'value' => $severity
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
    
    /**
     * Get comprehensive vulnerability data with severity breakdown for a month
     */
    public function getMonthlyVulnerabilityData($startTime, $endTime) {
        $severities = [
            'critical' => self::SEVERITY_CRITICAL,
            'high' => self::SEVERITY_HIGH,
            'medium' => self::SEVERITY_MEDIUM,
            'low' => self::SEVERITY_LOW
        ];
        
        $data = [
            'new' => [],
            'closed' => [],
            'totals' => ['new' => 0, 'closed' => 0]
        ];
        
        foreach ($severities as $name => $value) {
            $newCount = $this->getNewVulnerabilitiesBySeverity($startTime, $endTime, $value);
            $closedCount = $this->getClosedVulnerabilitiesBySeverity($startTime, $endTime, $value);
            
            $data['new'][$name] = $newCount;
            $data['closed'][$name] = $closedCount;
            $data['totals']['new'] += $newCount;
            $data['totals']['closed'] += $closedCount;
        }
        
        return $data;
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
?>