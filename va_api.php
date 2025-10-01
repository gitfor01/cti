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
    $methodsUsed = [];
    
    // Analyze each month
    for ($i = $monthsToAnalyze - 1; $i >= 0; $i--) {
        list($startTime, $endTime) = getMonthTimestamps($i);
        $monthName = date('M Y', $startTime);
        
        $monthData = $api->getMonthlyVulnerabilityData($startTime, $endTime);
        
        // Track optimization methods used
        foreach (['critical', 'high', 'medium', 'low'] as $sev) {
            if (isset($monthData['current'][$sev]['method'])) {
                $methodsUsed[$monthData['current'][$sev]['method']] = 
                    ($methodsUsed[$monthData['current'][$sev]['method']] ?? 0) + 1;
            }
        }
        
        $netChange = $monthData['totals']['new'] - $monthData['totals']['closed'];
        
        $vgi = calculateVGI(
            $monthData['vgi_data']['critical_asset_instances'],
            $monthData['vgi_data']['high_asset_instances'],
            $monthData['vgi_data']['medium_asset_instances'],
            $monthData['vgi_data']['low_asset_instances']
        );
        
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
 * Get start and end timestamps for a month offset from current date
 * @param int $monthsAgo Number of months before current month (0 = current month)
 * @return array [startTimestamp, endTimestamp]
 */
function getMonthTimestamps($monthsAgo) {
    $now = time();
    $currentYear = (int)date('Y', $now);
    $currentMonth = (int)date('n', $now);
    
    // Calculate target month and year
    $targetMonth = $currentMonth - $monthsAgo;
    $targetYear = $currentYear;
    
    while ($targetMonth <= 0) {
        $targetMonth += 12;
        $targetYear--;
    }
    
    // Start of month (first day at 00:00:00)
    $startTime = mktime(0, 0, 0, $targetMonth, 1, $targetYear);
    
    // End of month (last day at 23:59:59)
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $targetMonth, $targetYear);
    $endTime = mktime(23, 59, 59, $targetMonth, $daysInMonth, $targetYear);
    
    return [$startTime, $endTime];
}

/**
 * Calculate Vulnerability Generic Index
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
    
    const SEVERITY_CRITICAL = '4';
    const SEVERITY_HIGH = '3';
    const SEVERITY_MEDIUM = '2';
    const SEVERITY_LOW = '1';
    const SEVERITY_INFO = '0';
    
    const WEIGHT_CRITICAL = 4;
    const WEIGHT_HIGH = 3;
    const WEIGHT_MEDIUM = 2;
    const WEIGHT_LOW = 1;
    
    const PAGE_SIZE = 5000;
    const MAX_VULNS_PER_SEVERITY = 50000;
    const MAX_BULK_REQUESTS = 100;
    
    public function __construct($host, $accessKey, $secretKey) {
        $this->host = rtrim($host, '/');
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    
    /**
     * Get complete monthly vulnerability data including VGI calculations
     * @param int $startTime Start timestamp
     * @param int $endTime End timestamp
     * @return array Complete month data with new, closed, current counts and VGI data
     */
    public function getMonthlyVulnerabilityData($startTime, $endTime) {
        $severities = [
            'critical' => self::SEVERITY_CRITICAL,
            'high' => self::SEVERITY_HIGH,
            'medium' => self::SEVERITY_MEDIUM,
            'low' => self::SEVERITY_LOW
        ];
        
        $monthData = [
            'new' => [],
            'closed' => [],
            'current' => [],
            'vgi_data' => [
                'critical_asset_instances' => 0,
                'high_asset_instances' => 0,
                'medium_asset_instances' => 0,
                'low_asset_instances' => 0
            ],
            'totals' => [
                'new' => 0,
                'closed' => 0
            ]
        ];
        
        // Get new and closed vulnerabilities for each severity
        foreach ($severities as $sevName => $sevValue) {
            $newCount = $this->getNewVulnerabilitiesBySeverity($startTime, $endTime, $sevValue);
            $closedCount = $this->getClosedVulnerabilitiesBySeverity($startTime, $endTime, $sevValue);
            
            $monthData['new'][$sevName] = $newCount;
            $monthData['closed'][$sevName] = $closedCount;
            $monthData['totals']['new'] += $newCount;
            $monthData['totals']['closed'] += $closedCount;
            
            // Get current asset instances for VGI calculation
            $assetData = $this->getVulnerabilityAssetInstances($endTime, $sevValue);
            $monthData['current'][$sevName] = $assetData;
            $monthData['vgi_data'][$sevName . '_asset_instances'] = $assetData['asset_instances'];
        }
        
        return $monthData;
    }
    
    /**
     * Test connection with lightweight query and better error handling
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
            // Use a VERY lightweight query - just check last 24 hours, limit 1
            $testEndTime = time();
            $testStartTime = $testEndTime - 86400; // Only last 24 hours
            
            $requestData = [
                'type' => 'vuln',
                'sourceType' => 'cumulative',
                'query' => [
                    'tool' => 'listvuln',
                    'type' => 'vuln',
                    'filters' => [
                        [
                            'filterName' => 'lastSeen',
                            'operator' => '>=',
                            'value' => (string)$testStartTime
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
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            
            $headers = [
                'x-apikey: accesskey=' . $this->accessKey . '; secretkey=' . $this->secretKey . ';',
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            
            $debugInfo['http_code'] = $httpCode;
            $debugInfo['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($curlErrno !== 0) {
                $debugInfo['error'] = "cURL Error ($curlErrno): $curlError";
                curl_close($ch);
                return $debugInfo;
            }
            
            curl_close($ch);
            
            if ($httpCode === 0) {
                $debugInfo['error'] = 'No response from server. Check URL and network connectivity.';
                return $debugInfo;
            }
            
            if ($httpCode === 401 || $httpCode === 403) {
                $debugInfo['error'] = "Authentication failed (HTTP $httpCode). Check your API keys.";
                return $debugInfo;
            }
            
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
            
            if (!isset($decoded['response'])) {
                $debugInfo['error'] = 'Unexpected response structure. Response: ' . substr(json_encode($decoded), 0, 200);
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
            $debugInfo['error'] = 'Exception: ' . $e->getMessage();
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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
     * Three-tier optimization approach
     */
    public function getVulnerabilityAssetInstances($endTime, $severity, $progressCallback = null) {
        $queryFilters = [
            [
                'filterName' => 'lastSeen',
                'operator' => '>=',
                'value' => ($endTime - (30 * 86400))
            ],
            [
                'filterName' => 'severity',
                'operator' => '=',
                'value' => $severity
            ]
        ];
        
        // ATTEMPT 1: Try sumid with count field
        error_log("VGI Calculation - Severity $severity: Attempting Method 1 (sumid with count field)");
        if ($progressCallback) call_user_func($progressCallback, "Trying Method 1: sumid with count field (fastest)...");
        $sumidResult = $this->tryGetAssetInstancesFromSumid($queryFilters, $progressCallback);
        if ($sumidResult !== false) {
            error_log("VGI Calculation - Severity $severity: SUCCESS with Method 1 (fastest!)");
            return array_merge($sumidResult, ['method' => 'sumid_count_field']);
        }
        
        // ATTEMPT 2: Bulk export
        error_log("VGI Calculation - Severity $severity: Method 1 failed, attempting Method 2 (bulk export)");
        if ($progressCallback) call_user_func($progressCallback, "Method 1 unavailable. Trying Method 2: bulk export (may take 30-60s)...");
        $bulkResult = $this->tryGetAssetInstancesFromBulkExport($queryFilters, $progressCallback);
        if ($bulkResult !== false) {
            error_log("VGI Calculation - Severity $severity: SUCCESS with Method 2 (recommended)");
            return array_merge($bulkResult, ['method' => 'bulk_export']);
        }
        
        // ATTEMPT 3: Individual queries
        error_log("VGI Calculation - Severity $severity: Method 2 failed, falling back to Method 3 (individual queries - SLOW)");
        if ($progressCallback) call_user_func($progressCallback, "Method 2 failed. Using Method 3: individual queries (VERY SLOW - may take several minutes)...");
        $individualResult = $this->getAssetInstancesFromIndividualQueries($queryFilters, $endTime, $progressCallback);
        error_log("VGI Calculation - Severity $severity: Completed with Method 3 (slowest method)");
        return array_merge($individualResult, ['method' => 'individual_queries']);
    }
    
    /**
     * ATTEMPT 1: Try sumid with count field
     */
    private function tryGetAssetInstancesFromSumid($queryFilters, $progressCallback = null) {
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
                'endOffset' => 10
            ];
            
            $response = $this->makeRequest('/analysis', 'POST', $requestData);
            
            if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                return false;
            }
            
            $results = $response['response']['results'];
            if (empty($results)) {
                return ['asset_instances' => 0, 'vuln_count' => 0, 'api_calls' => 1];
            }
            
            $firstResult = $results[0];
            if (!isset($firstResult['count'])) {
                error_log("VGI Test - sumid does NOT return 'count' field. Sample keys: " . implode(', ', array_keys($firstResult)));
                return false;
            }
            
            error_log("VGI Test - sumid DOES return 'count' field! Proceeding with full sumid query.");
            
            $totalAssetInstances = 0;
            $totalVulnCount = 0;
            $offset = 0;
            $hasMore = true;
            $pageCount = 0;
            
            while ($hasMore && $offset < self::MAX_VULNS_PER_SEVERITY) {
                $requestData['startOffset'] = $offset;
                $requestData['endOffset'] = $offset + self::PAGE_SIZE;
                
                if ($progressCallback && $pageCount > 0) {
                    call_user_func($progressCallback, "Method 1: fetching page " . ($pageCount + 1) . " (offset: $offset)...");
                }
                
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
                $pageCount++;
                $hasMore = ($offset < $totalRecords) && (count($results) === self::PAGE_SIZE);
            }
            
            return [
                'asset_instances' => $totalAssetInstances,
                'vuln_count' => $totalVulnCount,
                'api_calls' => 1
            ];
            
        } catch (Exception $e) {
            error_log("VGI Test - sumid method failed with error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ATTEMPT 2: Bulk export
     */
    private function tryGetAssetInstancesFromBulkExport($queryFilters, $progressCallback = null) {
        try {
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
            
            $vulnData = [];
            $offset = 0;
            $hasMore = true;
            $requestCount = 0;
            
            while ($hasMore && $requestCount < self::MAX_BULK_REQUESTS) {
                $requestData['startOffset'] = $offset;
                $requestData['endOffset'] = $offset + self::PAGE_SIZE;
                
                if ($progressCallback && $requestCount > 0) {
                    call_user_func($progressCallback, "Bulk export: fetching page " . ($requestCount + 1) . " (offset: $offset)...");
                }
                
                $response = $this->makeRequest('/analysis', 'POST', $requestData);
                
                if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                    break;
                }
                
                $results = $response['response']['results'];
                
                foreach ($results as $finding) {
                    if (!isset($finding['pluginID']) || !isset($finding['ip'])) {
                        continue;
                    }
                    
                    $pluginID = $finding['pluginID'];
                    $ip = $finding['ip'];
                    
                    if (!isset($vulnData[$pluginID])) {
                        $vulnData[$pluginID] = ['ips' => []];
                    }
                    
                    if (!in_array($ip, $vulnData[$pluginID]['ips'])) {
                        $vulnData[$pluginID]['ips'][] = $ip;
                    }
                }
                
                $offset += self::PAGE_SIZE;
                $requestCount++;
                
                if (count($results) < self::PAGE_SIZE) {
                    $hasMore = false;
                }
                
                if (count($vulnData) >= self::MAX_VULNS_PER_SEVERITY) {
                    error_log("VGI Test - Bulk export reached max vulns limit: " . self::MAX_VULNS_PER_SEVERITY);
                    break;
                }
            }
            
            $totalAssetInstances = 0;
            foreach ($vulnData as $pluginID => $data) {
                $totalAssetInstances += count($data['ips']);
            }
            
            error_log("VGI Test - Bulk export found $totalAssetInstances asset instances from " . count($vulnData) . " unique vulnerabilities");
            
            return [
                'asset_instances' => $totalAssetInstances,
                'vuln_count' => count($vulnData),
                'api_calls' => $requestCount
            ];
            
        } catch (Exception $e) {
            error_log("VGI Test - Bulk export failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ATTEMPT 3: Individual queries (slowest fallback method)
     * Queries each unique vulnerability individually to get affected assets
     */
    private function getAssetInstancesFromIndividualQueries($queryFilters, $endTime, $progressCallback = null) {
        try {
            // First, get list of unique vulnerabilities
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
                'endOffset' => self::PAGE_SIZE
            ];
            
            $uniqueVulns = [];
            $offset = 0;
            $hasMore = true;
            
            // Collect unique vulnerability IDs
            while ($hasMore && count($uniqueVulns) < self::MAX_VULNS_PER_SEVERITY) {
                $requestData['startOffset'] = $offset;
                $requestData['endOffset'] = $offset + self::PAGE_SIZE;
                
                $response = $this->makeRequest('/analysis', 'POST', $requestData);
                
                if (!isset($response['response']['results']) || !is_array($response['response']['results'])) {
                    break;
                }
                
                $results = $response['response']['results'];
                
                foreach ($results as $vuln) {
                    if (isset($vuln['pluginID'])) {
                        $uniqueVulns[] = $vuln['pluginID'];
                    }
                }
                
                $offset += self::PAGE_SIZE;
                $hasMore = (count($results) === self::PAGE_SIZE);
            }
            
            if (empty($uniqueVulns)) {
                return [
                    'asset_instances' => 0,
                    'vuln_count' => 0,
                    'api_calls' => 1
                ];
            }
            
            // Now query each vulnerability individually to get asset count
            $totalAssetInstances = 0;
            $apiCalls = 1; // Initial sumid call
            
            foreach ($uniqueVulns as $index => $pluginID) {
                if ($progressCallback && $index % 10 === 0) {
                    call_user_func($progressCallback, "Individual queries: processing " . ($index + 1) . " of " . count($uniqueVulns) . " vulnerabilities...");
                }
                
                $vulnFilters = array_merge($queryFilters, [
                    [
                        'filterName' => 'pluginID',
                        'operator' => '=',
                        'value' => $pluginID
                    ]
                ]);
                
                $vulnRequest = [
                    'type' => 'vuln',
                    'sourceType' => 'cumulative',
                    'query' => [
                        'tool' => 'sumip',
                        'type' => 'vuln',
                        'filters' => $vulnFilters
                    ],
                    'startOffset' => 0,
                    'endOffset' => 1
                ];
                
                try {
                    $vulnResponse = $this->makeRequest('/analysis', 'POST', $vulnRequest);
                    $apiCalls++;
                    
                    if (isset($vulnResponse['response']['totalRecords'])) {
                        $totalAssetInstances += (int)$vulnResponse['response']['totalRecords'];
                    }
                } catch (Exception $e) {
                    error_log("Failed to query plugin $pluginID: " . $e->getMessage());
                    continue;
                }
            }
            
            return [
                'asset_instances' => $totalAssetInstances,
                'vuln_count' => count($uniqueVulns),
                'api_calls' => $apiCalls
            ];
            
        } catch (Exception $e) {
            error_log("VGI Test - Individual queries failed: " . $e->getMessage());
            // Return zeros rather than false, as this is the final fallback
            return [
                'asset_instances' => 0,
                'vuln_count' => 0,
                'api_calls' => 1
            ];
        }
    }
}
?>