<?php
/**
 * Admin - Update Company Status
 * Allows admin to change company registration status
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed', 405);
}

try {
    // Require admin authentication
    $admin = requireAdminAuth();
    $admin_id = $admin->id;

    // Get PUT data
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->company_id) || !isset($data->status)) {
        Response::error('Company ID and status are required', 400);
    }

    $company_id = $data->company_id;
    $new_status = $data->status;

    // Validate status
    $valid_statuses = ['pending', 'payment_pending', 'payment_completed', 'active', 'suspended', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        Response::error('Invalid status value', 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if company exists
    $checkQuery = "SELECT id, company_name, registration_status FROM companies_registered WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $company_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        Response::notFound('Company not found');
    }

    $company = $checkStmt->fetch(PDO::FETCH_OBJ);
    $old_status = $company->registration_status;

    // Update status
    $updateQuery = "UPDATE companies_registered SET registration_status = :status WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':status', $new_status);
    $updateStmt->bindParam(':id', $company_id);
    $updateStmt->execute();

    // Log activity
    $logQuery = "INSERT INTO activity_logs (tenant_id, entity_type, entity_id, action, user_id, user_email, metadata)
                 VALUES (:tenant_id, 'company', :entity_id, 'status_updated', :user_id, :user_email, :metadata)";

    $logStmt = $db->prepare($logQuery);
    $metadata = json_encode([
        'old_status' => $old_status,
        'new_status' => $new_status,
        'admin_email' => $admin->email
    ]);

    // For admin actions on the portal, we use 'portal' as tenant_id
    $tenant_id = 'portal';

    $logStmt->bindParam(':tenant_id', $tenant_id);
    $logStmt->bindParam(':entity_id', $company_id);
    $logStmt->bindParam(':user_id', $admin_id);
    $logStmt->bindParam(':user_email', $admin->email);
    $logStmt->bindParam(':metadata', $metadata);
    $logStmt->execute();

    Response::success([
        'company' => [
            'id' => $company_id,
            'old_status' => $old_status,
            'new_status' => $new_status
        ]
    ], 'Company status updated successfully');

} catch (PDOException $e) {
    error_log("Database error in company-update-status.php: " . $e->getMessage());
    Response::serverError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error in company-update-status.php: " . $e->getMessage());
    Response::serverError('Server error: ' . $e->getMessage());
}
