<?php
/**
 * Admin - Toggle Company Active Status
 * PUT /api/admin/companies/{id}/toggle-active
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
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/jwt.php';

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

    // Get company ID from URL
    $requestUri = $_SERVER['REQUEST_URI'];
    preg_match('/companies\/([^\/]+)\/toggle-active/', $requestUri, $matches);
    $companyId = $matches[1] ?? null;

    if (!$companyId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Company ID required']);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // Get current status
    $query = "SELECT is_active FROM companies_registered WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $companyId);
    $stmt->execute();

    $company = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$company) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit();
    }

    // Toggle status
    $newStatus = !$company->is_active;

    $updateQuery = "UPDATE companies_registered SET is_active = :is_active WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':is_active', $newStatus, PDO::PARAM_BOOL);
    $updateStmt->bindParam(':id', $companyId);

    if ($updateStmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'is_active' => $newStatus,
            'message' => $newStatus ? 'Azienda attivata con successo' : 'Azienda disattivata con successo'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

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
