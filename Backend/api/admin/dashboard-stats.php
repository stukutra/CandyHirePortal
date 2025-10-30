<?php
/**
 * Admin - Dashboard Statistics
 * Returns overview statistics for admin dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Load composer autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';

try {
    // Verify JWT token
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization token required']);
        exit();
    }

    $jwt = new JWTHandler();
    $token = $matches[1];
    $decoded = $jwt->validateToken($token);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit();
    }

    // Verify it's an admin token
    if (!isset($decoded->data->type) || $decoded->data->type !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // Total companies
    $totalQuery = "SELECT COUNT(*) as total FROM companies_registered";
    $totalStmt = $db->query($totalQuery);
    $total_companies = $totalStmt->fetch(PDO::FETCH_OBJ)->total;

    // Active companies
    $activeQuery = "SELECT COUNT(*) as total FROM companies_registered WHERE registration_status = 'active'";
    $activeStmt = $db->query($activeQuery);
    $active_companies = $activeStmt->fetch(PDO::FETCH_OBJ)->total;

    // Payment pending
    $pendingQuery = "SELECT COUNT(*) as total FROM companies_registered WHERE registration_status = 'payment_pending'";
    $pendingStmt = $db->query($pendingQuery);
    $payment_pending = $pendingStmt->fetch(PDO::FETCH_OBJ)->total;

    // Payment completed
    $paidQuery = "SELECT COUNT(*) as total FROM companies_registered WHERE payment_status = 'completed'";
    $paidStmt = $db->query($paidQuery);
    $paid_companies = $paidStmt->fetch(PDO::FETCH_OBJ)->total;

    // Total revenue
    $revenueQuery = "SELECT SUM(amount) as total FROM payment_transactions WHERE status = 'completed'";
    $revenueStmt = $db->query($revenueQuery);
    $total_revenue = $revenueStmt->fetch(PDO::FETCH_OBJ)->total ?? 0;

    // Companies by status
    $statusQuery = "SELECT registration_status, COUNT(*) as count
                    FROM companies_registered
                    GROUP BY registration_status";
    $statusStmt = $db->query($statusQuery);
    $companies_by_status = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent registrations (last 7 days)
    $recentQuery = "SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM companies_registered
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
    $recentStmt = $db->query($recentQuery);
    $recent_registrations = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Latest companies (last 5)
    $latestQuery = "SELECT id, company_name, email, registration_status, payment_status, created_at
                    FROM companies_registered
                    ORDER BY created_at DESC
                    LIMIT 5";
    $latestStmt = $db->query($latestQuery);
    $latest_companies = $latestStmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_companies' => (int)$total_companies,
            'active_companies' => (int)$active_companies,
            'payment_pending' => (int)$payment_pending,
            'paid_companies' => (int)$paid_companies,
            'total_revenue' => (float)$total_revenue
        ],
        'companies_by_status' => $companies_by_status,
        'recent_registrations' => $recent_registrations,
        'latest_companies' => $latest_companies
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
