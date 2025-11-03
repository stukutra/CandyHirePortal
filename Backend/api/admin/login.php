<?php
/**
 * Admin Login Endpoint
 * Authenticates portal administrators
 */

// Handle preflight FIRST - before any other code
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (strpos($origin, 'http://localhost:') === 0 || strpos($origin, 'http://127.0.0.1:') === 0) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: *");
    }
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 3600");
    http_response_code(200);
    exit();
}

// CORS headers for actual requests
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (strpos($origin, 'http://localhost:') === 0 || strpos($origin, 'http://127.0.0.1:') === 0) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Load composer autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $jwt = new JWTHandler();

    // Get POST data
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input);

    // Log for debugging
    error_log("Raw input: " . $raw_input);
    error_log("Parsed data: " . json_encode($data));
    error_log("JSON last error: " . json_last_error_msg());

    if (!$data || !isset($data->email) || !isset($data->password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required',
            'debug' => [
                'raw_length' => strlen($raw_input),
                'data_type' => gettype($data),
                'json_error' => json_last_error_msg()
            ]
        ]);
        exit();
    }

    $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
    $password = $data->password;

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }

    // Find admin user
    $query = "SELECT id, username, email, password_hash, first_name, last_name, role, is_active
              FROM admin_users
              WHERE email = :email
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit();
    }

    $admin = $stmt->fetch(PDO::FETCH_OBJ);

    // Check if admin is active
    if (!$admin->is_active) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Account is inactive']);
        exit();
    }

    // Verify password
    if (!password_verify($password, $admin->password_hash)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit();
    }

    // Update last login
    $updateQuery = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':id', $admin->id);
    $updateStmt->execute();

    // Generate JWT token
    error_log("About to generate JWT for admin: " . $admin->id);

    $token_data = [
        'id' => $admin->id,
        'email' => $admin->email,
        'username' => $admin->username,
        'role' => $admin->role
    ];

    $token = $jwt->generateAdminToken($token_data);
    error_log("JWT generated: " . substr($token, 0, 20) . "...");

    // Set JWT as httpOnly cookie for security (not accessible from JavaScript)
    $is_production = getenv('APP_ENV') === 'production';
    setcookie(
        'portal_access_token',
        $token,
        [
            'expires' => time() + 86400, // 24 hours
            'path' => '/',
            'domain' => $is_production ? '.candyhire.cloud' : 'localhost',
            'secure' => $is_production, // HTTPS only in production
            'httponly' => true, // Not accessible from JavaScript
            'samesite' => 'Lax' // CSRF protection
        ]
    );

    // Return success with user data (NO TOKEN in response for security)
    $response = [
        'success' => true,
        'message' => 'Login successful',
        'admin' => [
            'id' => $admin->id,
            'username' => $admin->username,
            'email' => $admin->email,
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'role' => $admin->role
        ]
    ];

    error_log("Sending response: " . json_encode($response));
    http_response_code(200);
    echo json_encode($response);

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
