<?php
/**
 * Check if email already exists
 * Public endpoint - No authentication required
 *
 * POST /api/auth/check-email.php
 * Body: { "email": "user@example.com" }
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || empty($data->email)) {
    Response::error('Email is required', 400);
}

$email = trim($data->email);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if email exists in companies_registered
    $query = "SELECT id FROM companies_registered WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Email exists
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'exists' => true,
            'message' => 'This email is already registered'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    } else {
        // Email available
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'exists' => false,
            'message' => 'Email available'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

} catch (Exception $e) {
    error_log("Error checking email: " . $e->getMessage());
    Response::serverError('Server error occurred');
}
