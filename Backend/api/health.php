<?php
/**
 * Health Check Endpoint
 *
 * GET /api/health.php
 * Public endpoint - No authentication required
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'service' => 'CandyHire Portal API',
    'version' => '1.0.0'
];

// Check database connection
try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        $health['database'] = 'connected';

        // Test query
        $stmt = $db->query("SELECT COUNT(*) as count FROM tenant_pool");
        $result = $stmt->fetch();
        $health['tenant_pool_available'] = (int)$result->count;
    } else {
        $health['database'] = 'disconnected';
        $health['status'] = 'degraded';
    }
} catch (Exception $e) {
    $health['database'] = 'error';
    $health['database_error'] = $e->getMessage();
    $health['status'] = 'degraded';
}

http_response_code(200);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
