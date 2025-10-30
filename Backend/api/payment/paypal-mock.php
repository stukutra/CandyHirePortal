<?php
/**
 * PayPal Mock Payment Endpoint
 *
 * POST /api/payment/paypal-mock.php
 * Requires authentication
 *
 * Simulates PayPal payment completion and triggers tenant provisioning
 * IN DEVELOPMENT ONLY - In production, this will be replaced with real PayPal webhooks
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../services/TenantProvisioning.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Require company authentication
$company_data = requireCompanyAuth();

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Response::serverError('Database connection failed');
    }

    // Get company
    $company = new Company($db);
    if (!$company->findById($company_data->id)) {
        Response::notFound('Company not found');
    }

    // Check if already paid
    if ($company->payment_status === 'completed') {
        Response::success([
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'payment_status' => $company->payment_status,
                'tenant_schema' => $company->tenant_schema
            ],
            'message' => 'Payment already completed'
        ], 'Payment already processed');
    }

    // Simulate PayPal payment
    $paypal_subscription_id = 'MOCK-SUB-' . uniqid();
    $paypal_payer_id = 'MOCK-PAYER-' . uniqid();

    // Update payment status
    if (!$company->updatePaymentStatus('completed', $paypal_subscription_id, $paypal_payer_id)) {
        Response::serverError('Failed to update payment status');
    }

    // Log payment
    $logger = getLogger($db);
    $logger->logPayment($company->id, 'mock-transaction-' . uniqid(), 1500, 'completed');

    // Trigger tenant provisioning
    $provisioning = new TenantProvisioning($db, $logger);
    $result = $provisioning->provisionTenant($company->id);

    if (!$result['success']) {
        Response::serverError('Payment completed but tenant provisioning failed: ' . $result['error']);
    }

    // Reload company to get updated data with tenant
    $company->findById($company->id);

    // Generate new JWT with tenant_schema
    $jwt_handler = new JWTHandler();

    $updated_company_data = [
        'id' => $company->id,
        'email' => $company->email,
        'company_name' => $company->company_name,
        'tenant_schema' => $company->tenant_schema
    ];

    $access_token = $jwt_handler->generateToken($updated_company_data);
    $refresh_token = $jwt_handler->generateRefreshToken($updated_company_data);

    // Set new tokens with tenant info
    $jwt_handler->setTokenCookies($access_token, $refresh_token);

    // Return success response
    Response::success([
        'company' => [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'email' => $company->email,
            'payment_status' => $company->payment_status,
            'registration_status' => $company->registration_status,
            'tenant_schema' => $company->tenant_schema,
            'is_active' => (bool)$company->is_active
        ],
        'tenant' => [
            'schema' => $result['tenant_schema'],
            'status' => 'provisioned',
            'message' => $result['message']
        ],
        'message' => 'Payment completed and tenant provisioned successfully! You can now access CandyHire platform.',
        'redirect_url' => 'http://localhost:4202/dashboard' // CandyHire SaaS
    ], 'Payment and provisioning successful');

} catch (Exception $e) {
    error_log("PayPal mock payment error: " . $e->getMessage());
    Response::serverError('An error occurred during payment processing');
}
