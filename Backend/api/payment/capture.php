<?php
/**
 * PayPal Payment Capture Endpoint
 *
 * POST /api/payment/capture.php
 * Public endpoint - Called after PayPal approval
 *
 * Captures the approved PayPal order and updates company status
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

header('Content-Type: application/json');

error_log("========== PAYMENT CAPTURE REQUEST START ==========");

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERROR: Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    Response::error('Method not allowed', 405);
}

// Get posted data
$raw_input = file_get_contents("php://input");
error_log("Raw input length: " . strlen($raw_input));
error_log("Raw input: " . $raw_input);
$data = json_decode($raw_input);
error_log("JSON decoded: " . ($data ? "SUCCESS" : "FAILED"));

if ($data) {
    error_log("Token: " . ($data->token ?? 'N/A'));
}

// Validate required fields
if (empty($data->token)) {
    error_log("ERROR: Missing token");
    Response::validationError(['token' => 'PayPal order token is required'], 'Missing order token');
}

error_log("Step 1: Token validated: " . $data->token);

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

    $paypal_order_id = $data->token;
    error_log("Step 3: Looking for transaction with PayPal order ID: " . $paypal_order_id);

    // Find the transaction
    $stmt = $db->prepare("
        SELECT id, company_id, amount, currency, status
        FROM payment_transactions
        WHERE paypal_order_id = ?
    ");
    $stmt->execute([$paypal_order_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        error_log("ERROR: Transaction not found for PayPal order ID: " . $paypal_order_id);
        Response::error('Transaction not found', 404);
    }

    error_log("Step 3 Complete: Transaction found - ID: " . $transaction['id'] . ", Status: " . $transaction['status']);

    // Check if already processed
    if ($transaction['status'] === 'completed') {
        error_log("INFO: Transaction already completed");

        // Get company and tenant info
        $stmt = $db->prepare("
            SELECT c.id, c.company_name, c.tenant_id, t.tenant_id as pool_tenant_id
            FROM companies_registered c
            LEFT JOIN tenant_pool t ON c.tenant_id = t.tenant_id
            WHERE c.id = ?
        ");
        $stmt->execute([$transaction['company_id']]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        // Build tenant schema name if tenant is assigned
        if ($company['tenant_id']) {
            $company['schema_name'] = 'candyhire_tenant_' . $company['tenant_id'];
        }

        $saas_url = getenv('SAAS_URL') ?: 'http://localhost:4202';

        Response::success([
            'already_processed' => true,
            'payment_captured' => true,
            'transaction_id' => $transaction['id'],
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'tenant_assigned' => !empty($company['tenant_id']),
            'tenant_schema' => $company['schema_name'] ?? null,
            'redirect_url' => $saas_url,
            'message' => 'Payment already completed. Your account is active! Redirecting to your dashboard...'
        ], 'Payment already processed');
    }

    error_log("Step 4: Capturing PayPal order");
    // Capture the PayPal order
    $paypal = new PayPalClient();
    $capture_result = $paypal->captureOrder($paypal_order_id);
    error_log("Step 4 Complete: PayPal capture result status: " . ($capture_result['status'] ?? 'N/A'));

    // Check capture status
    if ($capture_result['status'] !== 'COMPLETED') {
        // Update transaction as failed
        $stmt = $db->prepare("
            UPDATE payment_transactions
            SET status = 'failed', error_message = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            'PayPal capture failed: ' . ($capture_result['status'] ?? 'Unknown error'),
            $transaction['id']
        ]);

        Response::error('Payment capture failed', 400);
    }

    // Extract PayPal details
    $payer_id = $capture_result['payer']['payer_id'] ?? null;
    $payer_email = $capture_result['payer']['email_address'] ?? null;
    $capture_id = null;

    if (isset($capture_result['purchase_units'][0]['payments']['captures'][0])) {
        $capture_id = $capture_result['purchase_units'][0]['payments']['captures'][0]['id'];
    }

    // Start transaction
    error_log("Step 5: Starting database transaction");
    $db->beginTransaction();

    try {
        error_log("Step 5.1: Updating payment transaction to completed");
        // Update payment transaction
        $stmt = $db->prepare("
            UPDATE payment_transactions
            SET
                status = 'completed',
                paypal_payer_id = ?,
                paypal_payer_email = ?,
                paypal_transaction_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $payer_id,
            $payer_email,
            $capture_id,
            $transaction['id']
        ]);
        error_log("Step 5.1 Complete: Payment transaction updated");

        error_log("Step 5.2: Updating company status to payment_completed");
        // Update company status
        $stmt = $db->prepare("
            UPDATE companies_registered
            SET
                registration_status = 'payment_completed',
                payment_status = 'completed',
                is_active = TRUE,
                paypal_payer_id = ?,
                subscription_start_date = CURDATE(),
                subscription_end_date = DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $payer_id,
            $transaction['company_id']
        ]);
        error_log("Step 5.2 Complete: Company status updated");

        error_log("Step 5.3: Looking for available tenant from pool");

        // Find available tenant from pool
        $stmt = $db->prepare("
            SELECT id, tenant_id
            FROM tenant_pool
            WHERE is_available = TRUE
            ORDER BY id ASC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute();
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            error_log("ERROR: No available tenant found in pool");
            throw new Exception("No available tenant databases. Please contact support.");
        }

        error_log("Step 5.3.1: Tenant found - Pool ID: " . $tenant['id'] . ", Tenant ID: " . $tenant['tenant_id']);

        // Assign tenant to company
        $stmt = $db->prepare("
            UPDATE tenant_pool
            SET is_available = FALSE, company_id = ?, assigned_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$transaction['company_id'], $tenant['id']]);
        error_log("Step 5.3.2: Tenant pool updated - marked as unavailable");

        // Update company with tenant info
        $stmt = $db->prepare("
            UPDATE companies_registered
            SET tenant_id = ?, tenant_assigned_at = NOW(), registration_status = 'active', is_active = TRUE
            WHERE id = ?
        ");
        $stmt->execute([$tenant['tenant_id'], $transaction['company_id']]);
        error_log("Step 5.3.3 Complete: Company assigned to tenant (tenant_id: " . $tenant['tenant_id'] . ") and status set to active");

        // Step 5.3.3.1: Add company admin to user_directory for O(1) login lookup
        // IMPORTANT: use legal_rep_email as the admin login identity
        error_log("Step 5.3.3.1: Adding company admin to user_directory (using legal_rep_email)");
        $stmt = $db->prepare("
            INSERT INTO user_directory (email, tenant_id, user_type, user_id, is_active)
            SELECT legal_rep_email, tenant_id, 'company_admin', id, is_active
            FROM companies_registered
            WHERE id = ?
        ");
        $stmt->execute([$transaction['company_id']]);
        error_log("Step 5.3.3.1 Complete: Company admin added to user_directory");

        // Step 5.3.4: Initialize tenant database with company data and first admin user
        error_log("Step 5.3.4: Initializing tenant database with first admin user");

        // Get full company data for tenant initialization
        $stmt = $db->prepare("
            SELECT * FROM companies_registered WHERE id = ?
        ");
        $stmt->execute([$transaction['company_id']]);
        $company_full_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company_full_data) {
            throw new Exception("Company data not found for tenant initialization");
        }

        // Build tenant database name
        $tenant_db_name = 'candyhire_tenant_' . $tenant['tenant_id'];
        error_log("Step 5.3.4.1: Tenant database name: " . $tenant_db_name);

        require_once __DIR__ . '/../utils/tenant_initializer.php';

        $tenant_initializer = new TenantInitializer($tenant_db_name, (string)$tenant['tenant_id']);
        $tenant_init_result = $tenant_initializer->initializeTenant($company_full_data);

        error_log("Step 5.3.4 Complete: Tenant initialized - User ID: " . $tenant_init_result['user_id']);

        // Step 5.3.5: Regenerate JWT with tenant info and new user_id
        error_log("Step 5.3.5: Regenerating JWT with tenant information");

        require_once __DIR__ . '/../config/jwt.php';
        $jwtHandler = new JWTHandler();

        $jwt_payload = [
            'id' => $company_full_data['id'],
            'email' => $company_full_data['email'],
            'company_name' => $company_full_data['company_name'],
            'tenant_id' => $tenant_init_result['tenant_id'],
            'tenant_schema' => $tenant_db_name,
            'user_id' => $tenant_init_result['user_id'],
            'role_id' => $tenant_init_result['role_id'],
            'type' => 'company_admin'
        ];

        $access_token = $jwtHandler->generateToken($jwt_payload);
        $refresh_token = $jwtHandler->generateRefreshToken($jwt_payload);

        // Update JWT cookies with tenant information
        $jwtHandler->setTokenCookies($access_token, $refresh_token);

        error_log("Step 5.3.5 Complete: JWT regenerated with tenant information");

        error_log("Step 5.4: Logging activity");
        // Log activity
        $logger = getLogger($db);
        $logger->logActivity(
            'company',
            $transaction['company_id'],
            'payment_completed',
            $transaction['company_id'],
            'company',
            [
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'paypal_order_id' => $paypal_order_id,
                'paypal_transaction_id' => $capture_id
            ]
        );
        error_log("Step 5.4 Complete: Activity logged");

        error_log("Step 5.5: Committing transaction");
        $db->commit();
        error_log("Step 5.5 Complete: Transaction committed successfully");

        // Return success with redirect URL to SaaS
        $saas_url = getenv('SAAS_URL') ?: 'http://localhost:4202';
        error_log("========== PAYMENT CAPTURE SUCCESS ==========");
        Response::success([
            'payment_captured' => true,
            'transaction_id' => $transaction['id'],
            'paypal_order_id' => $paypal_order_id,
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'tenant_assigned' => true,
            'tenant_schema' => $tenant_db_name,
            'tenant_id' => $tenant_init_result['tenant_id'],
            'user_id' => $tenant_init_result['user_id'],
            'redirect_url' => $saas_url,
            'message' => 'Payment completed successfully. Your tenant has been assigned and your account is now active! Redirecting to your dashboard...'
        ], 'Payment captured successfully');

    } catch (Exception $e) {
        error_log("ERROR in transaction: " . $e->getMessage());
        error_log("Rolling back transaction");
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("========== PAYMENT CAPTURE ERROR ==========");
    error_log("Error message: " . $e->getMessage());
    error_log("Error file: " . $e->getFile() . ":" . $e->getLine());
    error_log("Error trace: " . $e->getTraceAsString());
    error_log("========== PAYMENT CAPTURE REQUEST END (ERROR) ==========");
    Response::serverError('An error occurred while processing payment');
}
