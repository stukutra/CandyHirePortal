<?php
/**
 * PayPal Create Order Endpoint (Retry Payment)
 *
 * POST /api/payment/create-order.php
 * Authenticated endpoint - Creates a new PayPal order for companies with pending payment
 *
 * Used when:
 * - User cancelled previous payment
 * - Previous payment failed
 * - User wants to retry payment after registration
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

error_log("========== CREATE ORDER REQUEST START ==========");

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERROR: Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    Response::error('Method not allowed', 405);
}

try {
    error_log("Step 1: Authenticating user");
    // Authenticate user
    $auth_result = authenticateCompany();

    if (!$auth_result['success']) {
        error_log("ERROR: Authentication failed");
        Response::unauthorized($auth_result['message']);
    }

    $company_id = $auth_result['data']['id'];
    error_log("Step 1 Complete: User authenticated - Company ID: " . $company_id);

    error_log("Step 2: Connecting to database");
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        error_log("ERROR: Database connection failed");
        Response::serverError('Database connection failed');
    }
    error_log("Step 2 Complete: Database connected");

    error_log("Step 3: Fetching company details");
    // Get company details
    $stmt = $db->prepare("
        SELECT
            id, email, company_name, subscription_plan,
            registration_status, payment_status
        FROM companies_registered
        WHERE id = ?
    ");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        error_log("ERROR: Company not found");
        Response::error('Company not found', 404);
    }

    error_log("Step 3 Complete: Company found - Status: " . $company['payment_status']);

    // Check if payment is already completed
    if ($company['payment_status'] === 'completed') {
        error_log("INFO: Payment already completed");
        Response::error('Payment already completed. Your account is active.', 400);
    }

    error_log("Step 4: Creating PayPal order");
    // Create PayPal order
    $paypal = new PayPalClient();

    // Define plan prices (in EUR)
    $plan_prices = [
        'ultimate' => 1500.00
    ];

    $amount = $plan_prices[$company['subscription_plan']] ?? 1500.00;
    $currency = 'EUR';
    $description = "CandyHire {$company['subscription_plan']} Plan - Annual Subscription";

    error_log("Creating PayPal order - Amount: $amount, Currency: $currency");

    $order = $paypal->createOrder($amount, $currency, $description, [
        'company_id' => $company['id'],
        'subscription_plan' => $company['subscription_plan']
    ]);

    error_log("PayPal order created successfully. Order ID: " . ($order['id'] ?? 'N/A'));

    if (!isset($order['id'])) {
        error_log("ERROR: PayPal order creation failed - No order ID");
        Response::serverError('Failed to create PayPal order');
    }

    $paypal_order_id = $order['id'];
    $paypal_approval_url = null;

    // Extract approval URL
    if (isset($order['links'])) {
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $paypal_approval_url = $link['href'];
                break;
            }
        }
    }

    if (!$paypal_approval_url) {
        error_log("ERROR: No approval URL in PayPal response");
        Response::serverError('Failed to get PayPal approval URL');
    }

    error_log("PayPal approval URL obtained");

    error_log("Step 5: Saving transaction to database");
    // Save transaction to database
    $stmt = $db->prepare("
        INSERT INTO payment_transactions (
            company_id,
            amount,
            currency,
            paypal_order_id,
            status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
    ");

    $stmt->execute([
        $company['id'],
        $amount,
        $currency,
        $paypal_order_id
    ]);

    error_log("Step 5 Complete: Transaction saved to database");

    // Log activity
    $logger = getLogger($db);
    $logger->logActivity(
        'company',
        $company['id'],
        'payment_order_created',
        $company['id'],
        'company',
        [
            'paypal_order_id' => $paypal_order_id,
            'amount' => $amount,
            'currency' => $currency
        ]
    );

    error_log("========== CREATE ORDER SUCCESS ==========");

    // Return success with approval URL
    Response::success([
        'order_created' => true,
        'paypal_order_id' => $paypal_order_id,
        'approval_url' => $paypal_approval_url,
        'amount' => $amount,
        'currency' => $currency
    ], 'PayPal order created successfully');

} catch (Exception $e) {
    error_log("========== CREATE ORDER ERROR ==========");
    error_log("Error message: " . $e->getMessage());
    error_log("Error file: " . $e->getFile() . ":" . $e->getLine());
    error_log("Error trace: " . $e->getTraceAsString());
    error_log("========== CREATE ORDER REQUEST END (ERROR) ==========");
    Response::serverError('An error occurred while creating payment order');
}
