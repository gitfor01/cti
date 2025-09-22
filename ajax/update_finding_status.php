<?php
/**
 * AJAX endpoint to update finding status to 'Risk Raised'
 */

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/pcf_functions.php';

try {
    // Get database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Get the status to update to
    $status = $input['status'] ?? 'Risk Raised';
    $allowedStatuses = ['Risk Raised', 'Closed'];
    
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception('Invalid status. Allowed: ' . implode(', ', $allowedStatuses));
    }
    
    // Handle single finding update
    if (isset($input['finding_id'])) {
        $findingId = intval($input['finding_id']);
        $result = updateFindingStatus($pdo, $findingId, $status);
        echo json_encode($result);
        exit;
    }
    
    // Handle bulk update
    if (isset($input['finding_ids']) && is_array($input['finding_ids'])) {
        $findingIds = array_map('intval', $input['finding_ids']);
        $result = updateMultipleFindingsStatus($pdo, $findingIds, $status);
        echo json_encode($result);
        exit;
    }
    
    throw new Exception('No finding ID(s) provided');
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>