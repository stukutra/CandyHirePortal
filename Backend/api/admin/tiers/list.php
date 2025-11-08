<?php
/**
 * Admin: Get All Subscription Tiers
 * Returns all tiers with pagination and filters
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

try {
    $database = new Database();
    $db = $database->getConnection();
    $tierModel = new SubscriptionTier($db);

    // Get query parameters for filtering
    $filters = [];

    if (isset($_GET['is_enabled'])) {
        $filters['is_enabled'] = (int) $_GET['is_enabled'];
    }

    if (isset($_GET['is_featured'])) {
        $filters['is_featured'] = (int) $_GET['is_featured'];
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }

    // Get tiers and total count
    $tiers = $tierModel->getAll($filters);
    $total = $tierModel->getCount($filters);

    Response::success([
        'tiers' => $tiers,
        'total' => $total,
        'count' => count($tiers)
    ]);

} catch (Exception $e) {
    error_log("Error in admin/tiers/list.php: " . $e->getMessage());
    Response::error('Failed to fetch subscription tiers', 500);
}
