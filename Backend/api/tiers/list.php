<?php
/**
 * Get Pricing Tiers List
 * Public endpoint - No authentication required
 *
 * GET /api/tiers/list.php
 * Returns list of available subscription tiers/plans
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Define available pricing tiers
    // Currently we have a single "Professional" tier with Early Bird pricing
    $tiers = [
        [
            'id' => 'professional',
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Complete solution for recruitment agencies',
            'price' => 99.00,
            'currency' => 'EUR',
            'billing_period' => 'year',
            'discount_percentage' => 50,
            'original_price' => 198.00,
            'is_early_bird' => true,
            'is_featured' => true,
            'features' => [
                'unlimited_positions' => true,
                'unlimited_candidates' => true,
                'unlimited_users' => true,
                'unlimited_companies' => true,
                'analytics' => true,
                'calendar' => true,
                'revenue_tracking' => true,
                'automatic_backup' => true,
                'demo_data' => true,
                'priority_support' => true,
                'free_updates' => true
            ],
            'limits' => [
                'positions' => -1,  // -1 = unlimited
                'candidates' => -1,
                'users' => -1,
                'companies' => -1,
                'storage_gb' => 100
            ],
            'is_active' => true,
            'sort_order' => 1
        ]
    ];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'tiers' => $tiers,
        'count' => count($tiers)
    ]);

} catch (Exception $e) {
    error_log("Error in tiers/list.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
