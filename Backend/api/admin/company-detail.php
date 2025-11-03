<?php
/**
 * Admin - Company Detail
 * Returns detailed information about a specific company
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    // Require admin authentication
    $admin = requireAdminAuth();

    // Get company ID
    if (!isset($_GET['id'])) {
        Response::error('Company ID is required', 400);
    }

    $company_id = $_GET['id'];

    $database = new Database();
    $db = $database->getConnection();

    // Get company details
    $query = "SELECT * FROM companies_registered WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $company_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        Response::notFound('Company not found');
    }

    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get payment transactions
    $transQuery = "SELECT
                    id,
                    transaction_type,
                    amount,
                    currency,
                    status,
                    paypal_order_id,
                    paypal_subscription_id,
                    paypal_payer_id,
                    paypal_payer_email,
                    paypal_transaction_id,
                    metadata,
                    error_message,
                    created_at,
                    updated_at
                   FROM payment_transactions
                   WHERE company_id = :company_id
                   ORDER BY created_at DESC";

    $transStmt = $db->prepare($transQuery);
    $transStmt->bindParam(':company_id', $company_id);
    $transStmt->execute();
    $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse metadata JSON
    foreach ($transactions as &$transaction) {
        if (!empty($transaction['metadata'])) {
            $transaction['metadata'] = json_decode($transaction['metadata'], true);
        }
    }

    // Get activity logs
    $logsQuery = "SELECT
                    action,
                    user_id,
                    user_type,
                    ip_address,
                    metadata,
                    created_at
                  FROM activity_logs
                  WHERE entity_type = 'company' AND entity_id = :company_id
                  ORDER BY created_at DESC
                  LIMIT 50";

    $logsStmt = $db->prepare($logsQuery);
    $logsStmt->bindParam(':company_id', $company_id);
    $logsStmt->execute();
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse logs metadata
    foreach ($logs as &$log) {
        if (!empty($log['metadata'])) {
            $log['metadata'] = json_decode($log['metadata'], true);
        }
    }

    // Send response with flat structure expected by frontend
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'company' => $company,
        'transactions' => $transactions,
        'activity_logs' => $logs
    ]);
    exit();

} catch (PDOException $e) {
    error_log("Database error in company-detail.php: " . $e->getMessage());
    Response::serverError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error in company-detail.php: " . $e->getMessage());
    Response::serverError('Server error: ' . $e->getMessage());
}
