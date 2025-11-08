<?php
/**
 * Admin: Create New Subscription Tier
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

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['name', 'slug', 'category', 'price', 'features'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Response::error("Field '$field' is required", 400);
        }
    }

    // Check if slug already exists
    $tierModel = new SubscriptionTier($db);
    if ($tierModel->findBySlug($data['slug'])) {
        Response::error('A tier with this slug already exists', 400);
    }

    // Create new tier
    $tierModel = new SubscriptionTier($db);
    $tierModel->name = $data['name'];
    $tierModel->slug = $data['slug'];
    $tierModel->category = $data['category'];
    $tierModel->description = $data['description'] ?? null;
    $tierModel->price = (float) $data['price'];
    $tierModel->currency = $data['currency'] ?? 'EUR';
    $tierModel->billing_period = $data['billing_period'] ?? 'yearly';
    $tierModel->original_price = isset($data['original_price']) ? (float) $data['original_price'] : null;
    $tierModel->features = $data['features']; // Should be array
    $tierModel->highlights = $data['highlights'] ?? null;
    $tierModel->badge_text = $data['badge_text'] ?? null;
    $tierModel->badge_icon = $data['badge_icon'] ?? null;
    $tierModel->is_featured = isset($data['is_featured']) ? (bool) $data['is_featured'] : false;
    $tierModel->is_enabled = isset($data['is_enabled']) ? (bool) $data['is_enabled'] : true;
    $tierModel->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
    $tierModel->metadata = $data['metadata'] ?? null;

    if ($tierModel->create()) {
        Response::success([
            'message' => 'Tier created successfully',
            'tier' => $tierModel->toArray()
        ], 201);
    } else {
        Response::error('Failed to create tier', 500);
    }

} catch (Exception $e) {
    error_log("Error in admin/tiers/create.php: " . $e->getMessage());
    Response::error('Failed to create tier: ' . $e->getMessage(), 500);
}
