<?php
/**
 * CORS Configuration
 *
 * Handles Cross-Origin Resource Sharing for API access
 */

// Get allowed origins from environment or use defaults
$allowed_origins_env = getenv('CORS_ALLOWED_ORIGINS');
$allowed_origins = $allowed_origins_env
    ? explode(',', $allowed_origins_env)
    : ['http://localhost:4200', 'http://localhost:4201', 'http://localhost:4202'];

// Get the origin of the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';

// For development, allow localhost origins
if (strpos($origin, 'http://localhost:') === 0) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // Fallback for development
    header("Access-Control-Allow-Origin: *");
}

// Allowed HTTP methods
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

// Allowed headers
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

// Max age for preflight cache
header("Access-Control-Max-Age: 3600");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
