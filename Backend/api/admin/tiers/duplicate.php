<?php
/**
 * Admin: Duplicate Subscription Tier
 * Creates a copy of an existing tier with "is_enabled" set to false by default
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate tier ID
    if (!isset($data['id']) || empty($data['id'])) {
        Response::error('Tier ID is required', 400);
    }

    $tierId = (int) $data['id'];

    // Find existing tier
    $tierModel = new SubscriptionTier($db);
    if (!$tierModel->findById($tierId)) {
        Response::error('Tier not found', 404);
    }

    // Create a copy with modified values
    $newTier = new SubscriptionTier($db);
    $newTier->name = $tierModel->name . ' (Copy)';

    // Generate unique slug
    $baseSlug = $tierModel->slug . '-copy';
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $checkModel = new SubscriptionTier($db);
        if (!$checkModel->findBySlug($slug)) {
            break;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    $newTier->slug = $slug;
    $newTier->category = $tierModel->category;
    $newTier->description = $tierModel->description;
    $newTier->price = $tierModel->price;
    $newTier->currency = $tierModel->currency;
    $newTier->billing_period = $tierModel->billing_period;
    $newTier->original_price = $tierModel->original_price;
    $newTier->features = $tierModel->features;
    $newTier->highlights = $tierModel->highlights;
    $newTier->badge_text = $tierModel->badge_text;
    $newTier->badge_icon = $tierModel->badge_icon;
    $newTier->is_featured = false; // Disable featured by default
    $newTier->is_enabled = false; // Disable by default as requested
    $newTier->sort_order = $tierModel->sort_order;
    $newTier->metadata = $tierModel->metadata;

    if ($newTier->create()) {
        Response::success([
            'message' => 'Tier duplicated successfully',
            'tier' => $newTier->toArray()
        ], 201);
    } else {
        Response::error('Failed to duplicate tier', 500);
    }

} catch (Exception $e) {
    error_log("Error in admin/tiers/duplicate.php: " . $e->getMessage());
    Response::error('Failed to duplicate tier: ' . $e->getMessage(), 500);
}
