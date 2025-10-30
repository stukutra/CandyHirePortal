<?php
/**
 * Admin - Companies List
 * Returns all registered companies with filtering and pagination
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

    $database = new Database();
    $db = $database->getConnection();

    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

    // Build WHERE clause
    $whereConditions = [];
    $params = [];

    if (!empty($search)) {
        $whereConditions[] = "(company_name LIKE :search OR email LIKE :search OR vat_number LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
        $whereConditions[] = "registration_status = :status";
        $params[':status'] = $status;
    }

    if (!empty($payment_status)) {
        $whereConditions[] = "payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM companies_registered $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_OBJ)->total;

    // Get companies
    $query = "SELECT
                id,
                company_name,
                vat_number,
                email,
                phone,
                website,
                city,
                country,
                industry,
                employees_count,
                legal_rep_first_name,
                legal_rep_last_name,
                legal_rep_email,
                registration_status,
                payment_status,
                subscription_plan,
                subscription_start_date,
                subscription_end_date,
                tenant_schema,
                tenant_assigned_at,
                paypal_subscription_id,
                is_active,
                email_verified,
                created_at,
                last_login
              FROM companies_registered
              $whereClause
              ORDER BY created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate pagination
    $totalPages = ceil($totalRecords / $limit);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $companies,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => (int)$totalRecords,
            'per_page' => $limit
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
