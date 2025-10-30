<?php
/**
 * Company Login Endpoint
 *
 * POST /api/auth/login.php
 * Public endpoint - No authentication required
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->email) || empty($data->password)) {
    Response::validationError(
        ['email' => 'Email is required', 'password' => 'Password is required'],
        'Email and password are required'
    );
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Response::serverError('Database connection failed');
    }

    // Initialize Company model
    $company = new Company($db);

    // Check if company exists
    if (!$company->findByEmail($data->email)) {
        Response::unauthorized('Invalid email or password');
    }

    // Verify password
    if (!$company->verifyPassword($data->password)) {
        Response::unauthorized('Invalid email or password');
    }

    // Check if company is active (has completed payment)
    if (!$company->is_active && $company->payment_status !== 'completed') {
        Response::forbidden('Your registration is pending payment. Please complete your payment to access the platform.');
    }

    // Update last login
    $company->updateLastLogin();

    // Generate JWT tokens
    $jwt_handler = new JWTHandler();

    $company_data = [
        'id' => $company->id,
        'email' => $company->email,
        'company_name' => $company->company_name,
        'tenant_schema' => $company->tenant_schema
    ];

    $access_token = $jwt_handler->generateToken($company_data);
    $refresh_token = $jwt_handler->generateRefreshToken($company_data);

    // Set tokens in httpOnly cookies (secure storage)
    $jwt_handler->setTokenCookies($access_token, $refresh_token);

    // Log successful login
    $logger = getLogger($db);
    $logger->logLogin($company->id, $company->email, true);

    // Return success response with company data only (tokens in cookies)
    Response::success([
        'company' => [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'email' => $company->email,
            'tenant_schema' => $company->tenant_schema,
            'registration_status' => $company->registration_status,
            'payment_status' => $company->payment_status,
            'is_active' => (bool)$company->is_active
        ],
        'message' => 'Authentication successful. Tokens set in secure cookies.'
    ], 'Login successful');

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    Response::serverError('An error occurred during login');
}
