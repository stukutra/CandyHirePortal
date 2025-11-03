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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (empty($data->token)) {
    Response::validationError(['token' => 'PayPal order token is required'], 'Missing order token');
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Response::serverError('Database connection failed');
    }

    $paypal_order_id = $data->token;

    // Find the transaction
    $stmt = $db->prepare("
        SELECT id, company_id, amount, currency, status
        FROM payment_transactions
        WHERE paypal_order_id = ?
    ");
    $stmt->execute([$paypal_order_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        Response::error('Transaction not found', 404);
    }

    // Check if already processed
    if ($transaction['status'] === 'completed') {
        Response::success([
            'already_processed' => true,
            'message' => 'Payment already completed'
        ], 'Payment already processed');
    }

    // Capture the PayPal order
    $paypal = new PayPalClient();
    $capture_result = $paypal->captureOrder($paypal_order_id);

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
    $db->beginTransaction();

    try {
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

        // Update company status
        $stmt = $db->prepare("
            UPDATE companies_registered
            SET
                registration_status = 'payment_completed',
                payment_status = 'completed',
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
            // Assign tenant to company
            $stmt = $db->prepare("
                UPDATE tenant_pool
                SET is_available = FALSE, company_id = ?, assigned_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$transaction['company_id'], $tenant['id']]);

            // Update company with tenant info
            $stmt = $db->prepare("
                UPDATE companies_registered
                SET tenant_id = ?, tenant_assigned_at = NOW(), registration_status = 'active'
                WHERE id = ?
            ");
            $stmt->execute([$tenant['id'], $transaction['company_id']]);
        }

        // Log activity
        $logger = getLogger($db);
        $logger->log(
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

        $db->commit();

        // Return success
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
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Payment capture error: " . $e->getMessage());
    Response::serverError('An error occurred while processing payment');
}
