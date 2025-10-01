<?php
/**
 * VA Dashboard API - Tenable Security Center Integration with VGI
 * Handles vulnerability data retrieval and analysis with Generic Index
 * Optimized with three-tier approach to minimize API requests
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
    $previousVGI = 0;
    $methodsUsed = []; // Track which optimization methods were used
    
    // Analyze each month
    for ($i = $monthsToAnalyze - 1; $i >= 0; $i--) {
        list($startTime, $endTime) = getMonthTimestamps($i);
        $monthName = date('M Y', $startTime);
        
        // Get comprehensive vulnerability data with severity breakdown and asset counts
        $monthData = $api->getMonthlyVulnerabilityData($startTime, $endTime);
        
        // Track optimization methods used
        foreach (['critical', 'high', 'medium', 'low'] as $sev) {
            if (isset($monthData['current'][$sev]['method'])) {
                $methodsUsed[$monthData['current'][$sev]['method']] = 
                    ($methodsUsed[$monthData['current'][$sev]['method']] ?? 0) + 1;
            }
        }
        
        // Calculate net change
        $netChange = $monthData['totals']['new'] - $monthData['totals']['closed'];
        
        // Calculate VGI using the corrected formula
        // VGI = Sum of (severity_weight × affected_asset_count) for each vulnerability / 100
        $vgi = calculateVGI(
            $monthData['vgi_data']['critical_asset_instances'],
            $monthData['vgi_data']['high_asset_instances'],
            $monthData['vgi_data']['medium_asset_instances'],
            $monthData['vgi_data']['low_asset_instances']
        );
        
        // Calculate VGI change from previous month
        $vgiChange = $vgi - $previousVGI;
        $previousVGI = $vgi;
        
        $results[] = [
            'month' => $monthName,
            'new' => $monthData['totals']['new'],
            'closed' => $monthData['totals']['closed'],
            'net' => $netChange,
            'vgi' => round($vgi, 2),
            'vgiChange' => round($vgiChange, 2),
            'severity' => [
                'new' => $monthData['new'],
                'closed' => $monthData['closed']
            ],
            'current' => [
                'critical' => $monthData['current']['critical'],
                'high' => $monthData['current']['high'],
                'medium' => $monthData['current']['medium'],
                'low' => $monthData['current']['low']
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'summary' => [
            'totalNew' => array_sum(array_column($results, 'new')),
            'totalClosed' => array_sum(array_column($results, 'closed')),
            'totalNet' => array_sum(array_column($results, 'net')),
            'currentVGI' => end($results)['vgi'],
            'avgVGIChange' => round(array_sum(array_column($results, 'vgiChange')) / count($results), 2)
        ],
        'optimization' => [
            'methodsUsed' => $methodsUsed,
            'note' => 'Methods: sumid_count_field (fastest), bulk_export (recommended), individual_queries (slowest)'
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
 * Calculate Vulnerability Generic Index
 * Formula: ((Critical_instances×4) + (High_instances×3) + (Medium_instances×2) + (Low_instances×1)) ÷ 100
 * Where instances = sum of affected assets across all vulnerabilities of that severity
 */
function calculateVGI($criticalAssetInstances, $highAssetInstances, 
                      $mediumAssetInstances, $lowAssetInstances) {
    $criticalScore = 4 * $criticalAssetInstances;
    $highScore = 3 * $highAssetInstances;
    $mediumScore = 2 * $mediumAssetInstances;
    $lowScore = 1 * $lowAssetInstances;
    
    $totalScore = $criticalScore + $highScore + $mediumScore + $lowScore;
    
    return $totalScore / 100;
}

/**
 * Tenable Security Center API Client with Optimized VGI Calculation
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
    
    // Severity weights for VGI calculation
    const WEIGHT_CRITICAL = 4;
    const WEIGHT_HIGH = 3;
    const WEIGHT_MEDIUM = 2;
    const WEIGHT_LOW = 1;
    
    // Pagination settings
    const PAGE_SIZE = 5000; // Fetch 5000 records per request
    const MAX_VULNS_PER_SEVERITY = 50000; // Safety limit: max 50k vulnerabilities per severity
    const MAX_BULK_REQUESTS = 100; // Safety limit for bulk export
    
    public function __construct($host, $accessKey, $secretKey) {
        $this->host = rtrim($host, '/');
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    
    /**
     * Test connection and return detailed debug information
     * @return array Connection details including response time, HTTP code, and sample data
     */
    public function testConnection() {
        $startTime = microtime(true);
        $debugInfo = [
            'host' => $this->host,
            'endpoint' => '/rest/analysis',
            'success' => false,
            'http_code' => null,
            'response_time_ms' => 0,
            'error' => null,
            'sample_response' => null
        ];
        
        try {
            // Make a simple test query
            $testEndTime = time();
            $testStartTime = $testEndTime - (30 * 86400); // Last 30 days
            
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'listvuln',
                    'type' => 'vuln',
                    'filters' => [
                        [
                            'filterName' => 'firstSeen',
                            'operator' => '=',
                            'value' => $testStartTime . '-' . $testEndTime
                        ],
                        [
                            'filterName' => 'severity',
                            'operator' => '=',
                            'value' => '4'
                        ]
                    ]
                ],
                'startOffset' => 0,
                'endOffset' => 1
            ];
            
            $url = $this->host . '/rest/analysis';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            $headers = [
                'x-apikey: accesskey=' . $this->accessKey . '; secretkey=' . $this->secretKey . ';',
                'Content-Type: application/json'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $debugInfo['http_code'] = $httpCode;
            
            if (curl_errno($ch)) {
                $debugInfo['error'] = 'cURL Error: ' . curl_error($ch);
                curl_close($ch);
                return $debugInfo;
            }
            
            curl_close($ch);
            
            $debugInfo['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($httpCode !== 200) {
                $debugInfo['error'] = "HTTP $httpCode - " . substr($response, 0, 200);
                return $debugInfo;
            }
            
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $debugInfo['error'] = 'Invalid JSON response: ' . json_last_error_msg();
                $debugInfo['sample_response'] = substr($response, 0, 200);
                return $debugInfo;
            }
            
            $debugInfo['success'] = true;
            $debugInfo['sample_response'] = [
                'type' => $decoded['type'] ?? 'unknown',
                'response_type' => $decoded['response']['type'] ?? 'unknown',
                'total_records' => $decoded['response']['totalRecords'] ?? 0,
                'has_results' => isset($decoded['response']['results'])
            ];
            
        } catch (Exception $e) {
            $debugInfo['error'] = $e->getMessage();
            $debugInfo['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
        }
        
        return $debugInfo;
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased timeout for large responses
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
            throw new Exception("API request failed with status code: $httpCode. Response: " . substr($response, 0, 200));
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        return $decoded;
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
     * Get total asset instances for vulnerabilities of a specific severity
     * Three-tier optimization approach:
     * 1. FIRST TRY: Check if sumid returns a 'count' field directly (fastest)
     * 2. SECOND TRY: Export all vulndetails and aggregate locally (recommended by Tenable)
     * 3. FALLBACK: Query each plugin individually (slowest, ~100k+ requests)
     * 
     * @param int $endTime End timestamp for the analysis period
     * @param string $severity Severity level (4=Critical, 3=High, 2=Medium, 1=Low)
     * @return array ['asset_instances' => total, 'vuln_count' => count, 'method_used' => string]
     */
    public function getVulnerabilityAssetInstances($endTime, $severity) {
        $queryFilters = [
            [
                'filterName' => 'lastSeen',
                'operator' => '>=',
                'value' => ($endTime - (30 * 86400)) // Last 30 days
            ],
            [
                'filterName' => 'severity',
                'operator' => '=',
                'value' => $severity
            ]
        ];
        
        // ATTEMPT 1: Try sumid to see if it provides count field directly
        error_log("VGI Calculation - Severity $severity: Attempting Method 1 (sumid with count field)");
        $sumidResult = $this->tryGetAssetInstancesFromSumid($queryFilters);
        if ($sumidResult !== false) {
            error_log("VGI Calculation - Severity $severity: SUCCESS with Method 1 (fastest!)");
            return array_merge($sumidResult, ['method_used' => 'sumid_count_field']);
        }
        
        // ATTEMPT 2: Bulk export with vulndetails and local aggregation (Tenable recommended)
        error_log("VGI Calculation - Severity $severity: Method 1 failed, attempting Method 2 (bulk export)");
        $bulkResult = $this->tryGetAssetInstancesFromBulkExport($queryFilters);
        if ($bulkResult !== false) {
            error_log("VGI Calculation - Severity $severity: SUCCESS with Method 2 (recommended)");
            return array_merge($bulkResult, ['method_used' => 'bulk_export']);
        }
        
        // ATTEMPT 3: Fallback to individual plugin queries (slow but guaranteed)
        error_log("VGI Calculation - Severity $severity: Method 2 failed, falling back to Method 3 (individual queries - SLOW)");
        $individualResult = $this->getAssetInstancesFromIndividualQueries($queryFilters, $endTime);
        error_log("VGI Calculation - Severity $severity: Completed with Method 3 (slowest method - consider optimization)");
        return array_merge($individualResult, ['method_used' => 'individual_queries']);
    }
    
    /**
     * ATTEMPT 1: Try to get asset instances from sumid if it returns count field
     * Tests first 10 records to see if 'count' field is available
     * 
     * @param array $queryFilters Filters for the query
     * @return array|false Returns result array or false if method doesn't work
     */
    private function tryGetAssetInstancesFromSumid($queryFilters) {
        try {
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'sumid',
                    'type' => 'vuln',
                    'filters' => $queryFilters
                ],
                'sortField' => 'severity',
                'sortDirection' => 'desc',
                'startOffset' => 0,
                'endOffset' => 10 // Just test with first 10 records
            ];
            
            $response = $this->makeRequest('/analysis', 'POST', $requestData);
            
            if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                return false;
            }
            
            $results = $response['response']['results'];
            if (empty($results)) {
                // No vulnerabilities found for this severity
                return ['asset_instances' => 0, 'vuln_count' => 0];
            }
            
            // Check if first result has a 'count' field
            $firstResult = $results[0];
            if (!isset($firstResult['count'])) {
                error_log("VGI Test - sumid does NOT return 'count' field. Sample keys: " . implode(', ', array_keys($firstResult)));
                return false;
            }
            
            error_log("VGI Test - sumid DOES return 'count' field! Proceeding with full sumid query.");
            
            // Success! Now get all results with pagination
            $totalAssetInstances = 0;
            $totalVulnCount = 0;
            $offset = 0;
            $hasMore = true;
            
            while ($hasMore && $offset < self::MAX_VULNS_PER_SEVERITY) {
                $requestData['query']['startOffset'] = $offset;
                $requestData['query']['endOffset'] = $offset + self::PAGE_SIZE;
                
                $response = $this->makeRequest('/analysis', 'POST', $requestData);
                
                if (!isset($response['response']['results'])) {
                    break;
                }
                
                $results = $response['response']['results'];
                
                foreach ($results as $vuln) {
                    $totalAssetInstances += (int)$vuln['count'];
                    $totalVulnCount++;
                }
                
                $totalRecords = isset($response['response']['totalRecords']) 
                    ? (int)$response['response']['totalRecords'] 
                    : 0;
                
                $offset += self::PAGE_SIZE;
                $hasMore = ($offset < $totalRecords) && (count($results) === self::PAGE_SIZE);
            }
            
            return [
                'asset_instances' => $totalAssetInstances,
                'vuln_count' => $totalVulnCount
            ];
            
        } catch (Exception $e) {
            error_log("VGI Test - sumid method failed with error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ATTEMPT 2: Bulk export all vulnerabilities and aggregate locally
     * This is the Tenable-recommended approach for large datasets
     * Exports raw vulnerability data and counts unique IPs per plugin locally
     * 
     * @param array $queryFilters Filters for the query
     * @return array|false Returns result array or false if method doesn't work
     */
    private function tryGetAssetInstancesFromBulkExport($queryFilters) {
        try {
            // Use vulndetails tool for raw vulnerability data (Tenable recommended)
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'vulndetails',
                    'type' => 'vuln',
                    'filters' => $queryFilters
                ],
                'sortField' => 'severity',
                'sortDirection' => 'desc',
                'startOffset' => 0,
                'endOffset' => self::PAGE_SIZE
            ];
            
            // Data structure: pluginID => [ip1 => true, ip2 => true, ...]
            // Using IPs as keys provides automatic deduplication
            $vulnData = [];
            $offset = 0;
            $hasMore = true;
            $requestCount = 0;
            
            while ($hasMore && $requestCount < self::MAX_BULK_REQUESTS) {
                $requestData['query']['startOffset'] = $offset;
                $requestData['query']['endOffset'] = $offset + self::PAGE_SIZE;
                
                $response = $this->makeRequest('/analysis', 'POST', $requestData);
                
                if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                    break;
                }
                
                $results = $response['response']['results'];
                
                // Aggregate locally: group by pluginID and collect unique IPs
                foreach ($results as $finding) {
                    if (!isset($finding['pluginID']) || !isset($finding['ip'])) {
                        continue; // Skip malformed records
                    }
                    
                    $pluginID = $finding['pluginID'];
                    $ip = $finding['ip'];
                    
                    if (!isset($vulnData[$pluginID])) {
                        $vulnData[$pluginID] = [];
                    }
                    
                    // Using IP as array key automatically handles deduplication
                    $vulnData[$pluginID][$ip] = true;
                }
                
                $totalRecords = isset($response['response']['totalRecords']) 
                    ? (int)$response['response']['totalRecords'] 
                    : 0;
                
                $offset += self::PAGE_SIZE;
                $hasMore = ($offset < $totalRecords) && (count($results) === self::PAGE_SIZE);
                $requestCount++;
            }
            
            // Calculate total asset instances by summing unique IPs per vulnerability
            $totalAssetInstances = 0;
            foreach ($vulnData as $pluginID => $ips) {
                $totalAssetInstances += count($ips);
            }
            
            $totalVulnCount = count($vulnData);
            
            error_log("VGI Bulk Export - Made $requestCount requests, found $totalVulnCount unique vulnerabilities, $totalAssetInstances asset instances");
            
            return [
                'asset_instances' => $totalAssetInstances,
                'vuln_count' => $totalVulnCount
            ];
            
        } catch (Exception $e) {
            error_log("VGI Test - bulk export method failed with error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ATTEMPT 3: Individual plugin queries (slowest fallback method)
     * Only used when both optimized methods fail
     * Makes one API call per vulnerability to get IP counts
     * 
     * @param array $queryFilters Filters for the query
     * @param int $endTime End timestamp
     * @return array Returns result array (never fails, but very slow with many CVEs)
     */
    private function getAssetInstancesFromIndividualQueries($queryFilters, $endTime) {
        $totalAssetInstances = 0;
        $totalVulnCount = 0;
        $offset = 0;
        $hasMore = true;
        
        // Get all unique vulnerabilities using sumid with pagination
        while ($hasMore && $offset < self::MAX_VULNS_PER_SEVERITY) {
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'sumid',
                    'type' => 'vuln',
                    'filters' => $queryFilters
                ],
                'sortField' => 'severity',
                'sortDirection' => 'desc',
                'startOffset' => $offset,
                'endOffset' => $offset + self::PAGE_SIZE
            ];
            
            $response = $this->makeRequest('/analysis', 'POST', $requestData);
            
            if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                break;
            }
            
            $results = $response['response']['results'];
            
            // For each vulnerability, query for affected IPs individually (SLOW!)
            foreach ($results as $vuln) {
                if (isset($vuln['pluginID'])) {
                    $ipCount = $this->getIPCountForPlugin($vuln['pluginID'], $endTime);
                    $totalAssetInstances += $ipCount;
                    
                    // Add small delay to avoid rate limiting (10ms)
                    usleep(10000);
                }
                
                $totalVulnCount++;
            }
            
            $totalRecords = isset($response['response']['totalRecords']) 
                ? (int)$response['response']['totalRecords'] 
                : 0;
            
            $offset += self::PAGE_SIZE;
            $hasMore = ($offset < $totalRecords) && (count($results) === self::PAGE_SIZE);
        }
        
        return [
            'asset_instances' => $totalAssetInstances,
            'vuln_count' => $totalVulnCount
        ];
    }
    
    /**
     * Get the number of IPs affected by a specific plugin ID
     * Used by the individual queries fallback method
     * 
     * @param string $pluginID The Nessus plugin ID
     * @param int $endTime End timestamp for the analysis period
     * @return int Number of affected IPs
     */
    private function getIPCountForPlugin($pluginID, $endTime) {
        $queryFilters = [
            [
                'filterName' => 'pluginID',
                'operator' => '=',
                'value' => $pluginID
            ],
            [
                'filterName' => 'lastSeen',
                'operator' => '>=',
                'value' => ($endTime - (30 * 86400))
            ]
        ];
        
        $requestData = [
            'type' => 'vuln',
            'sourceType' => 'cumulative',
            'query' => [
                'tool' => 'sumip', // Groups by IP address
                'type' => 'vuln',
                'filters' => $queryFilters
            ],
            'startOffset' => 0,
            'endOffset' => 1 // We only need the totalRecords count
        ];
        
        try {
            $response = $this->makeRequest('/analysis', 'POST', $requestData);
            
            if (isset($response['response']['totalRecords'])) {
                return (int)$response['response']['totalRecords'];
            }
        } catch (Exception $e) {
            // If query fails, return 0
            return 0;
        }
        
        return 0;
    }
    
    /**
     * Get comprehensive vulnerability data with severity breakdown for a month
     * Now includes optimized VGI calculation with automatic method selection
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
            'current' => [],
            'vgi_data' => [
                'critical_asset_instances' => 0,
                'high_asset_instances' => 0,
                'medium_asset_instances' => 0,
                'low_asset_instances' => 0
            ],
            'totals' => ['new' => 0, 'closed' => 0]
        ];
        
        foreach ($severities as $name => $value) {
            // Get new and closed counts for display
            $newCount = $this->getNewVulnerabilitiesBySeverity($startTime, $endTime, $value);
            $closedCount = $this->getClosedVulnerabilitiesBySeverity($startTime, $endTime, $value);
            
            // Get asset instances for VGI calculation (tries 3 methods automatically)
            $vgiData = $this->getVulnerabilityAssetInstances($endTime, $value);
            
            $data['new'][$name] = $newCount;
            $data['closed'][$name] = $closedCount;
            $data['current'][$name] = [
                'count' => $vgiData['vuln_count'],
                'assets' => $vgiData['asset_instances'],
                'method' => $vgiData['method_used'] // Track which method was used
            ];
            $data['vgi_data'][$name . '_asset_instances'] = $vgiData['asset_instances'];
            
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