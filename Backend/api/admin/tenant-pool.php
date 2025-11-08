<?php
/**
 * Tenant Pool Management API
 * GET /api/admin/tenant-pool.php
 * Returns list of all tenant databases and their status
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json');

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    // Require admin authentication
    $admin = requireAdminAuth();

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
        ORDER BY tp.id ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $tenants = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tenants[] = [
            'id' => (int)$row['id'],
            'tenant_id' => (int)$row['tenant_id'],
            'schema_name' => 'candyhire_tenant_' . $row['tenant_id'],
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
    exit();

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
