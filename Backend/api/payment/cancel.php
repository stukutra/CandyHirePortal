<?php
/**
 * PayPal Payment Cancel Endpoint
 *
 * POST /api/payment/cancel.php
 * Public endpoint - Called when user cancels PayPal payment
 *
 * Updates transaction status and allows user to retry
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/logger.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Token is optional for cancel
$paypal_order_id = $data->token ?? null;

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Response::serverError('Database connection failed');
    }

    if ($paypal_order_id) {
        // Find and update the transaction
        $stmt = $db->prepare("
            SELECT id, company_id
            FROM payment_transactions
            WHERE paypal_order_id = ? AND status = 'pending'
        ");
        $stmt->execute([$paypal_order_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($transaction) {
            // Update transaction status
            $stmt = $db->prepare("
                UPDATE payment_transactions
                SET status = 'failed', error_message = 'User cancelled payment', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$transaction['id']]);

            // Log activity
            $logger = getLogger($db);
            $logger->log(
                'company',
                $transaction['company_id'],
                'payment_cancelled',
                $transaction['company_id'],
                'company',
                ['paypal_order_id' => $paypal_order_id]
            );
        }
    }

    Response::success([
        'cancelled' => true,
        'message' => 'Payment was cancelled. You can retry payment from your account dashboard.'
    ], 'Payment cancelled');

} catch (Exception $e) {
    error_log("Payment cancel error: " . $e->getMessage());
    Response::serverError('An error occurred while processing cancellation');
}
