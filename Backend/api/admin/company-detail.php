<?php
/**
 * Admin - Company Detail
 * Returns detailed information about a specific company
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    // Get company ID
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Company ID is required']);
        exit();
    }

    $company_id = $_GET['id'];

    $database = new Database();
    $db = $database->getConnection();

    // Get company details
    $query = "SELECT * FROM companies_registered WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $company_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit();
    }

    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get payment transactions
    $transQuery = "SELECT
                    id,
                    transaction_type,
                    amount,
                    currency,
                    status,
                    paypal_order_id,
                    paypal_subscription_id,
                    paypal_payer_id,
                    paypal_payer_email,
                    paypal_transaction_id,
                    metadata,
                    error_message,
                    created_at,
                    updated_at
                   FROM payment_transactions
                   WHERE company_id = :company_id
                   ORDER BY created_at DESC";

    $transStmt = $db->prepare($transQuery);
    $transStmt->bindParam(':company_id', $company_id);
    $transStmt->execute();
    $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse metadata JSON
    foreach ($transactions as &$transaction) {
        if (!empty($transaction['metadata'])) {
            $transaction['metadata'] = json_decode($transaction['metadata'], true);
        }
    }

    // Get activity logs
    $logsQuery = "SELECT
                    action,
                    user_id,
                    user_type,
                    ip_address,
                    metadata,
                    created_at
                  FROM activity_logs
                  WHERE entity_type = 'company' AND entity_id = :company_id
                  ORDER BY created_at DESC
                  LIMIT 50";

    $logsStmt = $db->prepare($logsQuery);
    $logsStmt->bindParam(':company_id', $company_id);
    $logsStmt->execute();
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse logs metadata
    foreach ($logs as &$log) {
        if (!empty($log['metadata'])) {
            $log['metadata'] = json_decode($log['metadata'], true);
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'company' => $company,
        'transactions' => $transactions,
        'activity_logs' => $logs
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
