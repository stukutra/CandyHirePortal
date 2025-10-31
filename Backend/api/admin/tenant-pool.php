<?php
/**
 * Tenant Pool Management API
 * GET /api/admin/tenant-pool.php
 * Returns list of all tenant databases and their status
 */

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (strpos($origin, 'http://localhost:') === 0 || strpos($origin, 'http://127.0.0.1:') === 0) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: *");
    }
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 3600");
    http_response_code(200);
    exit();
}

// CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (strpos($origin, 'http://localhost:') === 0 || strpos($origin, 'http://127.0.0.1:') === 0) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';

try {
    // Verify admin authentication
    $jwt = new JWTHandler();
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $token = substr($auth_header, 7);
    $decoded = $jwt->validateAdminToken($token);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit();
    }

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    // Query tenant pool with company information
    $query = "
        SELECT
            tp.id,
            tp.tenant_id,
            tp.is_available,
            tp.company_id,
            tp.assigned_at,
            tp.created_at,
            cr.company_name,
            cr.email,
            cr.registration_status,
            cr.payment_status
        FROM tenant_pool tp
        LEFT JOIN companies_registered cr ON tp.company_id = cr.id
        ORDER BY tp.tenant_id ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $tenants = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tenants[] = [
            'id' => (int)$row['id'],
            'tenant_id' => (int)$row['tenant_id'],
            'is_available' => (bool)$row['is_available'],
            'company_id' => $row['company_id'],
            'assigned_at' => $row['assigned_at'],
            'created_at' => $row['created_at'],
            'company' => $row['company_id'] ? [
                'name' => $row['company_name'],
                'email' => $row['email'],
                'registration_status' => $row['registration_status'],
                'payment_status' => $row['payment_status']
            ] : null
        ];
    }

    // Calculate statistics
    $stats = [
        'total' => count($tenants),
        'available' => 0,
        'assigned' => 0,
        'active' => 0
    ];

    foreach ($tenants as $tenant) {
        if ($tenant['is_available']) {
            $stats['available']++;
        } else {
            $stats['assigned']++;
            if ($tenant['company'] && $tenant['company']['registration_status'] === 'active') {
                $stats['active']++;
            }
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'tenants' => $tenants,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Tenant pool error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Tenant pool error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
