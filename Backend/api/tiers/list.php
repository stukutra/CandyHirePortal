<?php
/**
 * Get Subscription Tiers (Public)
 * Returns all enabled subscription tiers for display on pricing page
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../models/SubscriptionTier.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $tierModel = new SubscriptionTier($db);

    // Get only enabled tiers for public display
    $filters = ['is_enabled' => 1];
    $tiers = $tierModel->getAll($filters);

    Response::success([
        'tiers' => $tiers,
        'count' => count($tiers)
    ]);

} catch (Exception $e) {
    error_log("Error in tiers/list.php: " . $e->getMessage());
    Response::error('Failed to fetch subscription tiers', 500);
}
