<?php
/**
 * Admin - Update Company Status
 * Allows admin to change company registration status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Load composer autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';

try {
    // Verify JWT token
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization token required']);
        exit();
    }

    $jwt = new JWTHandler();
    $token = $matches[1];
    $decoded = $jwt->validateToken($token);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit();
    }

    // Verify it's an admin token
    if (!isset($decoded->data->type) || $decoded->data->type !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }

    $admin_id = $decoded->data->id;

    // Get PUT data
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->company_id) || !isset($data->status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Company ID and status are required']);
        exit();
    }

    $company_id = $data->company_id;
    $new_status = $data->status;

    // Validate status
    $valid_statuses = ['pending', 'payment_pending', 'payment_completed', 'active', 'suspended', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if company exists
    $checkQuery = "SELECT id, company_name, registration_status FROM companies_registered WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $company_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit();
    }

    $company = $checkStmt->fetch(PDO::FETCH_OBJ);
    $old_status = $company->registration_status;

    // Update status
    $updateQuery = "UPDATE companies_registered SET registration_status = :status WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':status', $new_status);
    $updateStmt->bindParam(':id', $company_id);
    $updateStmt->execute();

    // Log activity
    $logQuery = "INSERT INTO activity_logs (entity_type, entity_id, action, user_id, user_type, metadata)
                 VALUES ('company', :entity_id, 'status_updated', :user_id, 'admin', :metadata)";

    $logStmt = $db->prepare($logQuery);
    $metadata = json_encode([
        'old_status' => $old_status,
        'new_status' => $new_status,
        'admin_email' => $decoded->data->email
    ]);

    $logStmt->bindParam(':entity_id', $company_id);
    $logStmt->bindParam(':user_id', $admin_id);
    $logStmt->bindParam(':metadata', $metadata);
    $logStmt->execute();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Company status updated successfully',
        'company' => [
            'id' => $company_id,
            'old_status' => $old_status,
            'new_status' => $new_status
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
