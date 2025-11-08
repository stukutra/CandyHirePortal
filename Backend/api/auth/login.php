<?php
/**
 * Unified Login Endpoint
 *
 * POST /api/auth/login.php
 * Public endpoint - No authentication required
 *
 * Handles login for both:
 * - Company Admins (user_type: company_admin)
 * - Internal Users (user_type: internal_user)
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

header('Content-Type: application/json');

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

    // Step 1: Check if user exists in user_directory
    $stmt = $db->prepare("
        SELECT id, email, tenant_id, user_type, user_id, is_active
        FROM user_directory
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$data->email]);
    $user_dir = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_dir) {
        error_log("Login failed: User not found in user_directory - {$data->email}");
        Response::unauthorized('Invalid email or password');
    }

    // Check if user is active
    if (!$user_dir['is_active']) {
        Response::forbidden('Your account is not active. Please contact support.');
    }

    $tenant_id = $user_dir['tenant_id'];
    $user_type = $user_dir['user_type'];
    $user_id = $user_dir['user_id'];

    // Step 2: Get tenant schema from tenant_pool
    $stmt = $db->prepare("SELECT schema_name FROM tenant_pool WHERE id = ? LIMIT 1");
    $stmt->execute([$tenant_id]);
    $tenant_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tenant_row) {
        error_log("Login failed: Tenant not found in tenant_pool - tenant_id: {$tenant_id}");
        Response::serverError('Tenant configuration error');
    }

    $tenant_schema = $tenant_row['schema_name'];

    // Step 3: Connect to tenant database and verify password
    $database_tenant = new Database();
    $tenant_db = $database_tenant->getTenantConnection($tenant_schema);

    if (!$tenant_db) {
        error_log("Login failed: Cannot connect to tenant database - {$tenant_schema}");
        Response::serverError('Tenant database connection failed');
    }

    // Get user from tenant database
    $stmt = $tenant_db->prepare("
        SELECT id, tenant_id, email, password_hash, first_name, last_name,
               role_id, is_active, phone, avatar
        FROM system_users
        WHERE id = ? AND tenant_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $tenant_id]);
    $tenant_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tenant_user) {
        error_log("Login failed: User not found in tenant database - user_id: {$user_id}, tenant: {$tenant_schema}");
        Response::unauthorized('Invalid email or password');
    }

    // Verify password
    if (!password_verify($data->password, $tenant_user['password_hash'])) {
        error_log("Login failed: Invalid password for {$data->email}");
        Response::unauthorized('Invalid email or password');
    }

    // Check if user is active in tenant database
    if (!$tenant_user['is_active']) {
        Response::forbidden('Your account has been deactivated. Please contact your administrator.');
    }

    // Step 4: Get additional data based on user_type
    $company_data = null;
    $company_id = null;

    if ($user_type === 'company_admin') {
        // Get company data for company admins
        $stmt = $db->prepare("
            SELECT id, company_name, email as company_email, subscription_plan,
                   subscription_start_date, subscription_end_date, payment_status
            FROM companies_registered
            WHERE tenant_schema = ?
            LIMIT 1
        ");
        $stmt->execute([$tenant_schema]);
        $company_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($company_data) {
            $company_id = $company_data['id'];
        }
    }

    // Step 5: Update last login in tenant database
    $stmt = $tenant_db->prepare("UPDATE system_users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);

    // Step 6: Generate JWT tokens
    $jwt_handler = new JWTHandler();

    $jwt_data = [
        'id' => $user_id,
        'email' => $tenant_user['email'],
        'tenant_id' => (string)$tenant_id,
        'tenant_schema' => $tenant_schema,
        'user_id' => $user_id,
        'role_id' => $tenant_user['role_id'],
        'user_type' => $user_type,
        'type' => $user_type === 'company_admin' ? 'company_admin' : 'internal_user'
    ];

    // Add company_id for company admins
    if ($company_id) {
        $jwt_data['company_id'] = $company_id;
    }

    // Add company name if available
    if ($company_data) {
        $jwt_data['company_name'] = $company_data['company_name'];
    }

    $access_token = $jwt_handler->generateToken($jwt_data);
    $refresh_token = $jwt_handler->generateRefreshToken($jwt_data);

    // Set tokens in httpOnly cookies (secure storage)
    $jwt_handler->setTokenCookies($access_token, $refresh_token);

    // Step 7: Log successful login
    $logger = getLogger($db);
    $logger->logLogin($user_id, $tenant_user['email'], true, $user_type);

    // Step 8: Prepare response data
    $response_user = [
        'id' => $tenant_user['id'],
        'email' => $tenant_user['email'],
        'first_name' => $tenant_user['first_name'],
        'last_name' => $tenant_user['last_name'],
        'role_id' => $tenant_user['role_id'],
        'user_type' => $user_type,
        'tenant_id' => (string)$tenant_id,
        'tenant_schema' => $tenant_schema,
        'phone' => $tenant_user['phone'],
        'avatar' => $tenant_user['avatar']
    ];

    // Add company data for company admins
    if ($company_data) {
        $response_user['company'] = [
            'id' => $company_data['id'],
            'name' => $company_data['company_name'],
            'email' => $company_data['company_email'],
            'subscription_plan' => $company_data['subscription_plan'],
            'subscription_start_date' => $company_data['subscription_start_date'],
            'subscription_end_date' => $company_data['subscription_end_date'],
            'payment_status' => $company_data['payment_status']
        ];
    }

    // Get redirect URL
    $saas_url = getenv('SAAS_URL') ?: 'http://localhost:4202';

    // Return success response
    Response::success([
        'user' => $response_user,
        'redirect_url' => $saas_url,
        'message' => 'Authentication successful. Tokens set in secure cookies.'
    ], 'Login successful');

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    Response::serverError('An error occurred during login');
}
