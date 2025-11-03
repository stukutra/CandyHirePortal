<?php
/**
 * PayPal Payment Capture Endpoint
 *
 * POST /api/payment/capture.php
 * Public endpoint - Called after PayPal approval
 *
 * Captures the approved PayPal order and updates company status
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

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
        Response::success([
            'already_processed' => true,
            'message' => 'Payment already completed'
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

        error_log("Step 5.3: Looking for available tenant");
        // Assign tenant from pool
        $stmt = $db->prepare("
            SELECT id, schema_name
            FROM tenant_pool
            WHERE is_available = TRUE
            ORDER BY id ASC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute();
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tenant) {
            error_log("Step 5.3: Tenant found - ID: " . $tenant['id'] . ", Schema: " . $tenant['schema_name']);

            // Assign tenant to company
            $stmt = $db->prepare("
                UPDATE tenant_pool
                SET is_available = FALSE, company_id = ?, assigned_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$transaction['company_id'], $tenant['id']]);
            error_log("Step 5.3: Tenant pool updated");

            // Update company with tenant info
            $stmt = $db->prepare("
                UPDATE companies_registered
                SET tenant_id = ?, tenant_assigned_at = NOW(), registration_status = 'active', is_active = TRUE
                WHERE id = ?
            ");
            $stmt->execute([$tenant['id'], $transaction['company_id']]);
            error_log("Step 5.3 Complete: Company assigned to tenant and status set to active");
        } else {
            error_log("WARNING: No available tenant found in pool");
        }

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

        // Return success
        error_log("========== PAYMENT CAPTURE SUCCESS ==========");
        Response::success([
            'payment_captured' => true,
            'transaction_id' => $transaction['id'],
            'paypal_order_id' => $paypal_order_id,
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'tenant_assigned' => isset($tenant),
            'message' => 'Payment completed successfully. Your account is now active!'
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
