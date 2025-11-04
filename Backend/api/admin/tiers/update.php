<?php
/**
 * Admin: Update Subscription Tier
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

// Only allow PUT/PATCH requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'])) {
    Response::error('Method not allowed', 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get PUT data
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

    // Check if slug is being changed and if it conflicts
    if (isset($data['slug']) && $data['slug'] !== $tierModel->slug) {
        $checkModel = new SubscriptionTier($db);
        if ($checkModel->findBySlug($data['slug'])) {
            Response::error('A tier with this slug already exists', 400);
        }
    }

    // Update tier properties
    $tierModel->name = $data['name'] ?? $tierModel->name;
    $tierModel->slug = $data['slug'] ?? $tierModel->slug;
    $tierModel->category = $data['category'] ?? $tierModel->category;
    $tierModel->description = $data['description'] ?? $tierModel->description;
    $tierModel->price = isset($data['price']) ? (float) $data['price'] : $tierModel->price;
    $tierModel->currency = $data['currency'] ?? $tierModel->currency;
    $tierModel->billing_period = $data['billing_period'] ?? $tierModel->billing_period;
    $tierModel->original_price = isset($data['original_price']) ? (float) $data['original_price'] : $tierModel->original_price;
    $tierModel->features = $data['features'] ?? $tierModel->features;
    $tierModel->highlights = $data['highlights'] ?? $tierModel->highlights;
    $tierModel->badge_text = $data['badge_text'] ?? $tierModel->badge_text;
    $tierModel->badge_icon = $data['badge_icon'] ?? $tierModel->badge_icon;
    $tierModel->is_featured = isset($data['is_featured']) ? (bool) $data['is_featured'] : $tierModel->is_featured;
    $tierModel->is_enabled = isset($data['is_enabled']) ? (bool) $data['is_enabled'] : $tierModel->is_enabled;
    $tierModel->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : $tierModel->sort_order;
    $tierModel->metadata = $data['metadata'] ?? $tierModel->metadata;

    if ($tierModel->update()) {
        Response::success([
            'message' => 'Tier updated successfully',
            'tier' => $tierModel->toArray()
        ]);
    } else {
        Response::error('Failed to update tier', 500);
    }

} catch (Exception $e) {
    error_log("Error in admin/tiers/update.php: " . $e->getMessage());
    Response::error('Failed to update tier: ' . $e->getMessage(), 500);
}
