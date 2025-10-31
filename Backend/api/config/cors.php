<?php
/**
 * Unified CORS Configuration for CandyHire Platform
 * Works transparently in localhost and production
 *
 * Automatically detects environment and allows:
 * - localhost:4200 (Portal dev)
 * - localhost:4202 (SaaS dev)
 * - www.candyhire.cloud (Portal production)
 * - app.candyhire.cloud (SaaS production)
 */

// Load environment
require_once __DIR__ . '/bootstrap.php';

// Get the origin of the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Define allowed origins based on environment
$is_production = getenv('APP_ENV') === 'production';

if ($is_production) {
    // Production: Only allow candyhire.cloud domains
    $allowed_origins = [
        'https://www.candyhire.cloud',
        'https://candyhire.cloud',
        'https://app.candyhire.cloud'
    ];
} else {
    // Development: Allow localhost on any port + custom env origins
    $custom_origins = getenv('CORS_ALLOWED_ORIGINS')
        ? explode(',', getenv('CORS_ALLOWED_ORIGINS'))
        : [];

    $allowed_origins = array_merge([
        'http://localhost:4200',  // Portal Angular dev
        'http://localhost:4202',  // SaaS Angular dev
        'http://localhost:8082',  // Portal API
        'http://localhost:8080',  // SaaS API
        'http://127.0.0.1:4200',
        'http://127.0.0.1:4202'
    ], $custom_origins);
}

// Check if origin is allowed
$is_allowed = false;

if ($is_production) {
    // Strict matching for production
    $is_allowed = in_array($origin, $allowed_origins);
} else {
    // Flexible matching for development (allows any localhost port)
    if (preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
        $is_allowed = true;
    } else {
        $is_allowed = in_array($origin, $allowed_origins);
    }
}

// Set CORS headers
if ($is_allowed) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else if (!$is_production) {
    // Development fallback: allow all
    header("Access-Control-Allow-Origin: *");
}

// Standard CORS headers
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-Tenant-ID");
header("Access-Control-Max-Age: 3600");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
