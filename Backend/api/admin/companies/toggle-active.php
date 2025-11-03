<?php
/**
 * Admin - Toggle Company Active Status
 * PUT /api/admin/companies/{id}/toggle-active
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed', 405);
}

try {
    // Require admin authentication
    $admin = requireAdminAuth();

    // Get company ID from URL
    $requestUri = $_SERVER['REQUEST_URI'];
    preg_match('/companies\/([^\/]+)\/toggle-active/', $requestUri, $matches);
    $companyId = $matches[1] ?? null;

    if (!$companyId) {
        Response::error('Company ID required', 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Get current status
    $query = "SELECT is_active FROM companies_registered WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $companyId);
    $stmt->execute();

    $company = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$company) {
        Response::notFound('Company not found');
    }

    // Toggle status
    $newStatus = !$company->is_active;

    $updateQuery = "UPDATE companies_registered SET is_active = :is_active WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':is_active', $newStatus, PDO::PARAM_BOOL);
    $updateStmt->bindParam(':id', $companyId);

    if ($updateStmt->execute()) {
        Response::success([
            'is_active' => $newStatus
        ], $newStatus ? 'Azienda attivata con successo' : 'Azienda disattivata con successo');
    } else {
        Response::serverError('Failed to update status');
    }

} catch (PDOException $e) {
    error_log("Database error in toggle-active.php: " . $e->getMessage());
    Response::serverError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error in toggle-active.php: " . $e->getMessage());
    Response::serverError('Server error: ' . $e->getMessage());
}
