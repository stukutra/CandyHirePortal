<?php
/**
 * Admin: Delete Subscription Tier
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../models/SubscriptionTier.php';

// Verify admin authentication
$admin = requireAdminAuth();

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get tier ID from query parameter or request body
    $tierId = null;
    if (isset($_GET['id'])) {
        $tierId = (int) $_GET['id'];
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id'])) {
            $tierId = (int) $data['id'];
        }
    }

    if (!$tierId) {
        Response::error('Tier ID is required', 400);
    }

    // Find and delete tier
    $tierModel = new SubscriptionTier($db);
    if (!$tierModel->findById($tierId)) {
        Response::error('Tier not found', 404);
    }

    if ($tierModel->delete()) {
        Response::success([
            'message' => 'Tier deleted successfully'
        ]);
    } else {
        Response::error('Failed to delete tier', 500);
    }

} catch (Exception $e) {
    error_log("Error in admin/tiers/delete.php: " . $e->getMessage());
    Response::error('Failed to delete tier: ' . $e->getMessage(), 500);
}
