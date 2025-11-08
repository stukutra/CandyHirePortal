<?php
/**
 * Admin Logout API
 * POST /api/admin/logout.php
 * Clears httpOnly authentication cookie
 */

require_once __DIR__ . '/../config/cors.php';

header('Content-Type: application/json');

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Determine if we're in production
    $is_production = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production';

    // Clear the httpOnly cookie by setting it with a past expiration date
    setcookie(
        'portal_access_token',
        '',
        [
            'expires' => time() - 3600,  // Expire 1 hour ago
            'path' => '/',
            'domain' => $is_production ? '.candyhire.cloud' : 'localhost',
            'secure' => $is_production,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    exit();

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
