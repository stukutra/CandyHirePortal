<?php
/**
 * Admin: Get Single Subscription Tier
 * Returns a single tier by ID
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../models/SubscriptionTier.php';

// Verify admin authentication
$admin = requireAdminAuth();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get tier ID from query parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        Response::error('Tier ID is required', 400);
    }

    $tierId = (int) $_GET['id'];
    $tierModel = new SubscriptionTier($db);

    if (!$tierModel->findById($tierId)) {
        Response::error('Tier not found', 404);
    }

    Response::success([
        'tier' => $tierModel->toArray()
    ]);

} catch (Exception $e) {
    error_log("Error in admin/tiers/get.php: " . $e->getMessage());
    Response::error('Failed to fetch tier', 500);
}
