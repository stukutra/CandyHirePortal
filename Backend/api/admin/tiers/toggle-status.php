<?php
/**
 * Admin: Toggle Subscription Tier Status
 * Toggles is_enabled status for a tier
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        Response::error('Tier ID is required', 400);
    }

    $tierId = (int) $data['id'];

    // Find tier
    $tierModel = new SubscriptionTier($db);
    if (!$tierModel->findById($tierId)) {
        Response::error('Tier not found', 404);
    }

    // Toggle is_enabled status
    $tierModel->is_enabled = !$tierModel->is_enabled;

    if ($tierModel->update()) {
        Response::success([
            'message' => 'Tier status updated successfully',
            'tier' => $tierModel->toArray()
        ]);
    } else {
        Response::error('Failed to update tier status', 500);
    }

} catch (Exception $e) {
    error_log("Error in admin/tiers/toggle-status.php: " . $e->getMessage());
    Response::error('Failed to toggle tier status: ' . $e->getMessage(), 500);
}
