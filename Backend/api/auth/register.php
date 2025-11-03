<?php
/**
 * Company Registration Endpoint
 *
 * POST /api/auth/register.php
 * Public endpoint - No authentication required
 *
 * Creates company registration and returns JWT token
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
$errors = [];

// Company information
if (empty($data->company_name)) {
    $errors['company_name'] = 'Company name is required';
}
if (empty($data->email)) {
    $errors['email'] = 'Email is required';
} else if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
}

// Legal representative
if (empty($data->legal_rep_first_name)) {
    $errors['legal_rep_first_name'] = 'Legal representative first name is required';
}
if (empty($data->legal_rep_last_name)) {
    $errors['legal_rep_last_name'] = 'Legal representative last name is required';
}
if (empty($data->legal_rep_email)) {
    $errors['legal_rep_email'] = 'Legal representative email is required';
} else if (!filter_var($data->legal_rep_email, FILTER_VALIDATE_EMAIL)) {
    $errors['legal_rep_email'] = 'Invalid legal representative email format';
}

// Password
if (empty($data->password)) {
    $errors['password'] = 'Password is required';
} else if (strlen($data->password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

// Terms and privacy
if (empty($data->terms_accepted) || $data->terms_accepted !== true) {
    $errors['terms_accepted'] = 'You must accept the terms and conditions';
}
if (empty($data->privacy_accepted) || $data->privacy_accepted !== true) {
    $errors['privacy_accepted'] = 'You must accept the privacy policy';
}

if (!empty($errors)) {
    Response::validationError($errors, 'Validation failed');
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Response::serverError('Database connection failed');
    }

    // Check if email already exists
    $company = new Company($db);
    if ($company->findByEmail($data->email)) {
        Response::validationError(
            ['email' => 'This email is already registered'],
            'Email already exists'
        );
    }

    // Create company
    $company->id = Company::generateId();
    $company->company_name = htmlspecialchars(strip_tags($data->company_name));
    $company->vat_number = isset($data->vat_number) ? htmlspecialchars(strip_tags($data->vat_number)) : null;
    $company->sdi_code = isset($data->sdi_code) && !empty($data->sdi_code) ? htmlspecialchars(strip_tags($data->sdi_code)) : null;
    $company->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
    $company->phone = isset($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : null;
    $company->website = isset($data->website) ? filter_var($data->website, FILTER_SANITIZE_URL) : null;
    $company->address = isset($data->address) ? htmlspecialchars(strip_tags($data->address)) : null;
    $company->city = isset($data->city) ? htmlspecialchars(strip_tags($data->city)) : null;
    $company->postal_code = isset($data->postal_code) ? htmlspecialchars(strip_tags($data->postal_code)) : null;
    $company->province = isset($data->province) ? htmlspecialchars(strip_tags($data->province)) : null;
    $company->country = isset($data->country) ? htmlspecialchars(strip_tags($data->country)) : 'Italy';
    $company->country_code = isset($data->country_code) ? strtoupper(htmlspecialchars(strip_tags($data->country_code))) : 'IT';
    $company->industry = isset($data->industry) ? htmlspecialchars(strip_tags($data->industry)) : null;
    $company->employees_count = isset($data->employees_count) ? htmlspecialchars(strip_tags($data->employees_count)) : null;
    $company->description = isset($data->description) ? htmlspecialchars(strip_tags($data->description)) : null;

    // Legal representative
    $company->legal_rep_first_name = htmlspecialchars(strip_tags($data->legal_rep_first_name));
    $company->legal_rep_last_name = htmlspecialchars(strip_tags($data->legal_rep_last_name));
    $company->legal_rep_email = filter_var($data->legal_rep_email, FILTER_SANITIZE_EMAIL);
    $company->legal_rep_phone = isset($data->legal_rep_phone) ? htmlspecialchars(strip_tags($data->legal_rep_phone)) : null;

    // Password
    $company->password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    // Status
    $company->registration_status = 'payment_pending';
    $company->payment_status = 'pending';
    $company->subscription_plan = isset($data->subscription_plan) ? htmlspecialchars(strip_tags($data->subscription_plan)) : 'ultimate';
    $company->terms_accepted = $data->terms_accepted;
    $company->privacy_accepted = $data->privacy_accepted;

    // Create company
    if (!$company->create()) {
        Response::serverError('Failed to create company registration');
    }

    // Log registration
    $logger = getLogger($db);
    $logger->logRegistration($company->id, $company->email);

    // Generate JWT token
    $jwt_handler = new JWTHandler();

    $company_data = [
        'id' => $company->id,
        'email' => $company->email,
        'company_name' => $company->company_name,
        'tenant_schema' => null // Not assigned yet
    ];

    $access_token = $jwt_handler->generateToken($company_data);
    $refresh_token = $jwt_handler->generateRefreshToken($company_data);

    // Set tokens in httpOnly cookies
    $jwt_handler->setTokenCookies($access_token, $refresh_token);

    // Create PayPal order
    $paypal_approval_url = null;
    $paypal_order_id = null;

    error_log("Starting PayPal order creation for company: " . $company->id);

    try {
        error_log("Instantiating PayPalClient...");
        $paypal = new PayPalClient();
        error_log("PayPalClient instantiated successfully");

        // Define plan prices (in EUR)
        $plan_prices = [
            'ultimate' => 1500.00
        ];

        $amount = $plan_prices[$company->subscription_plan] ?? 1500.00;
        $currency = 'EUR';
        $description = "CandyHire {$company->subscription_plan} Plan - Annual Subscription";

        error_log("Creating PayPal order - Amount: $amount, Currency: $currency");

        // Create PayPal order with company metadata
        $order = $paypal->createOrder($amount, $currency, $description, [
            'company_id' => $company->id,
            'subscription_plan' => $company->subscription_plan
        ]);

        error_log("PayPal order created successfully. Order ID: " . ($order['id'] ?? 'N/A'));

        $paypal_order_id = $order['id'];

        // Find approval URL
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $paypal_approval_url = $link['href'];
                break;
            }
        }

        error_log("PayPal approval URL: " . ($paypal_approval_url ?? 'NOT FOUND'));

        // Create payment transaction record
        $transaction_id = 'TXN_' . strtoupper(bin2hex(random_bytes(8)));
        $stmt = $db->prepare("
            INSERT INTO payment_transactions (
                id, company_id, transaction_type, amount, currency,
                status, paypal_order_id, metadata
            ) VALUES (?, ?, 'subscription', ?, ?, 'pending', ?, ?)
        ");

        $metadata_json = json_encode([
            'subscription_plan' => $company->subscription_plan,
            'order_created_at' => date('Y-m-d H:i:s')
        ]);

        $stmt->execute([
            $transaction_id,
            $company->id,
            $amount,
            $currency,
            $paypal_order_id,
            $metadata_json
        ]);

    } catch (Exception $e) {
        error_log("PayPal order creation error: " . $e->getMessage());
        error_log("PayPal error trace: " . $e->getTraceAsString());
        // Don't fail registration if PayPal fails - admin can manually process
    }

    error_log("PayPal order creation completed. Approval URL: " . ($paypal_approval_url ?? 'NULL'));

    // Return success response
    $response_data = [
        'company' => [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'email' => $company->email,
            'registration_status' => $company->registration_status,
            'payment_status' => $company->payment_status,
            'subscription_plan' => $company->subscription_plan
        ],
        'next_step' => 'payment',
        'message' => 'Registration successful. Please proceed with payment.'
    ];

    // Add PayPal approval URL if available
    if ($paypal_approval_url) {
        error_log("Adding PayPal approval URL to response");
        $response_data['paypal_approval_url'] = $paypal_approval_url;
        $response_data['paypal_order_id'] = $paypal_order_id;
    } else {
        error_log("WARNING: No PayPal approval URL - registration will complete without payment redirect");
    }

    Response::success($response_data, 'Registration successful', 201);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    Response::serverError('An error occurred during registration');
}
