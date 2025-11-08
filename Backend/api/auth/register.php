<?php
/**
 * Company Registration Endpoint
 *
 * POST /api/auth/register.php
 * Public endpoint - No authentication required
 *
 * Creates company registration and returns JWT token
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/SubscriptionTier.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$raw_input = file_get_contents("php://input");
error_log("========== REGISTRATION REQUEST START ==========");
error_log("Raw input length: " . strlen($raw_input));
$data = json_decode($raw_input);
error_log("JSON decoded: " . ($data ? "SUCCESS" : "FAILED"));
if ($data) {
    error_log("Company: " . ($data->company_name ?? 'N/A'));
    error_log("Email: " . ($data->email ?? 'N/A'));
    error_log("VAT: " . ($data->vat_number ?? 'N/A'));
}

// Validate required fields
error_log("Step 1: Starting validation");
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

error_log("Step 1 Complete: Validation done. Errors count: " . count($errors));

if (!empty($errors)) {
    error_log("Validation failed with errors: " . json_encode($errors));
    Response::validationError($errors, 'Validation failed');
}

try {
    error_log("Step 2: Connecting to database");
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        error_log("ERROR: Database connection failed");
        Response::serverError('Database connection failed');
    }
    error_log("Step 2 Complete: Database connected");

    error_log("Step 3: Checking if email exists");
    // Check if email already exists
    $company = new Company($db);
    if ($company->findByEmail($data->email)) {
        error_log("ERROR: Email already exists: " . $data->email);
        Response::validationError(
            ['email' => 'This email is already registered'],
            'Email already exists'
        );
    }
    error_log("Step 3 Complete: Email is available");

    error_log("Step 4: Checking if VAT exists");
    // Check if VAT number already exists (if provided)
    if (!empty($data->vat_number)) {
        $vat_check_query = "SELECT id, company_name FROM companies_registered WHERE vat_number = :vat_number LIMIT 1";
        $vat_stmt = $db->prepare($vat_check_query);
        $vat_stmt->bindParam(':vat_number', $data->vat_number);
        $vat_stmt->execute();

        if ($vat_stmt->rowCount() > 0) {
            $existing_company = $vat_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("ERROR: VAT already exists: " . $data->vat_number);
            Response::validationError(
                ['vat_number' => 'This VAT number is already registered' . ($existing_company['company_name'] ? ' (' . $existing_company['company_name'] . ')' : '')],
                'VAT number already exists'
            );
        }
    }
    error_log("Step 4 Complete: VAT is available");

    error_log("Step 5: Preparing company object");
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

    error_log("Step 5 Complete: Company object prepared with ID: " . $company->id);

    error_log("Step 6: Inserting company into database");
    // Create company
    if (!$company->create()) {
        error_log("ERROR: Failed to create company in database");
        Response::serverError('Failed to create company registration');
    }
    error_log("Step 6 Complete: Company created successfully");

    error_log("Step 7: Logging registration");
    // Log registration
    $logger = getLogger($db);
    $logger->logRegistration($company->id, $company->email);
    error_log("Step 7 Complete: Registration logged");

    error_log("Step 8: Generating JWT tokens");
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
    error_log("Step 8 Complete: JWT tokens generated and cookies set");

    error_log("Step 9: Creating PayPal order");
    // Create PayPal order
    $paypal_approval_url = null;
    $paypal_order_id = null;

    error_log("Starting PayPal order creation for company: " . $company->id);

    try {
        error_log("Instantiating PayPalClient...");
        $paypal = new PayPalClient();
        error_log("PayPalClient instantiated successfully");

        // Fetch tier details from database
        $tierModel = new SubscriptionTier($db);
        $tier_found = $tierModel->findBySlug($company->subscription_plan);

        if (!$tier_found) {
            error_log("ERROR: Subscription tier not found for slug: " . $company->subscription_plan);
            Response::validationError(
                ['subscription_plan' => 'Invalid subscription plan selected'],
                'Invalid subscription plan'
            );
        }

        $amount = $tierModel->price;
        $currency = $tierModel->currency ?? 'EUR';
        $tier_name = $tierModel->name;
        $billing_period = $tierModel->billing_period;

        $period_label = match($billing_period) {
            'yearly' => 'Annual',
            'monthly' => 'Monthly',
            'one_time' => 'One-time',
            default => ''
        };

        $description = "CandyHire {$tier_name} Plan" . ($period_label ? " - {$period_label} Subscription" : "");

        error_log("Creating PayPal order - Tier: {$tier_name}, Amount: {$amount}, Currency: {$currency}");

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

    // Send response directly (not wrapped in 'data' field)
    http_response_code(201);
    $response_data['success'] = true;
    $json_response = json_encode($response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    error_log("========== SENDING FINAL RESPONSE ==========");
    error_log("Response code: 201");
    error_log("Response length: " . strlen($json_response));
    error_log("Response JSON: " . $json_response);
    error_log("========== REGISTRATION REQUEST END ==========");
    echo $json_response;
    exit();

} catch (Exception $e) {
    error_log("========== REGISTRATION ERROR ==========");
    error_log("Error message: " . $e->getMessage());
    error_log("Error file: " . $e->getFile() . ":" . $e->getLine());
    error_log("Error trace: " . $e->getTraceAsString());
    error_log("========== REGISTRATION REQUEST END (ERROR) ==========");
    Response::serverError('An error occurred during registration');
}
