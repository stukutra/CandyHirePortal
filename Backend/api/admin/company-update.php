<?php
/**
 * Admin - Update Company Information
 * Allows admin to update company details
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json');

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

    if (!isset($data->id)) {
        Response::error('Company ID is required', 400);
    }

    $company_id = $data->id;

    $database = new Database();
    $db = $database->getConnection();

    // Check if company exists
    $checkQuery = "SELECT id FROM companies_registered WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $company_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        Response::notFound('Company not found');
    }

    // Build update query dynamically based on provided fields
    $allowedFields = [
        'company_name',
        'vat_number',
        'sdi_code',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'postal_code',
        'province',
        'country',
        'country_code',
        'industry',
        'employees_count',
        'description',
        'legal_rep_first_name',
        'legal_rep_last_name',
        'legal_rep_email',
        'legal_rep_phone',
        'registration_status',
        'payment_status',
        'subscription_plan',
        'subscription_start_date',
        'subscription_end_date',
        'is_active',
        'email_verified'
    ];

    $updateFields = [];
    $params = [':id' => $company_id];

    foreach ($allowedFields as $field) {
        if (isset($data->$field)) {
            $updateFields[] = "$field = :$field";
            $params[":$field"] = $data->$field;
        }
    }

    if (empty($updateFields)) {
        Response::error('No fields to update', 400);
    }

    // Update company
    $updateQuery = "UPDATE companies_registered SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);

    foreach ($params as $key => $value) {
        $updateStmt->bindValue($key, $value);
    }

    $updateStmt->execute();

    // Log activity
    $logQuery = "INSERT INTO activity_logs (tenant_id, entity_type, entity_id, action, user_id, user_email, metadata)
                 VALUES (:tenant_id, 'company', :entity_id, 'company_updated', :user_id, :user_email, :metadata)";

    $logStmt = $db->prepare($logQuery);
    $metadata = json_encode([
        'updated_fields' => array_keys(array_diff_key($params, [':id' => null])),
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

    Response::success(null, 'Company updated successfully');

} catch (PDOException $e) {
    error_log("Database error in company-update.php: " . $e->getMessage());
    Response::serverError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error in company-update.php: " . $e->getMessage());
    Response::serverError('Server error: ' . $e->getMessage());
}
