<?php
/**
 * Admin - Dashboard Statistics
 * Returns overview statistics for admin dashboard
 */

// Load Composer autoloader FIRST to avoid conflicts
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    error_log("dashboard-stats.php: Starting execution");

    // Require admin authentication
    $admin = requireAdminAuth();
    error_log("dashboard-stats.php: Auth passed");

    $database = new Database();
    $db = $database->getConnection();
    error_log("dashboard-stats.php: DB connection obtained");

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

    // Latest companies with pagination, sorting, and filtering
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

    // Allowed sortable columns
    $allowedSort = ['company_name', 'email', 'city', 'subscription_plan', 'registration_status', 'payment_status', 'created_at'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'created_at';
    }

    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];

    // Search filter
    if (!empty($search)) {
        $whereConditions[] = "(company_name LIKE :search OR email LIKE :search OR vat_number LIKE :search OR city LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Status filters
    if (isset($_GET['registration_status']) && !empty($_GET['registration_status'])) {
        $whereConditions[] = "registration_status = :registration_status";
        $params[':registration_status'] = $_GET['registration_status'];
    }

    if (isset($_GET['payment_status']) && !empty($_GET['payment_status'])) {
        $whereConditions[] = "payment_status = :payment_status";
        $params[':payment_status'] = $_GET['payment_status'];
    }

    if (isset($_GET['subscription_plan']) && !empty($_GET['subscription_plan'])) {
        $whereConditions[] = "subscription_plan = :subscription_plan";
        $params[':subscription_plan'] = $_GET['subscription_plan'];
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Count total items for pagination
    $countQuery = "SELECT COUNT(*) as total FROM companies_registered $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalItems = (int)$countStmt->fetch(PDO::FETCH_OBJ)->total;
    $totalPages = ceil($totalItems / $limit);

    // Fetch companies with filters, sorting, and pagination
    $latestQuery = "SELECT
                        id,
                        company_name,
                        vat_number,
                        sdi_code,
                        email,
                        phone,
                        website,
                        address,
                        city,
                        postal_code,
                        province,
                        country,
                        country_code,
                        industry,
                        employees_count,
                        legal_rep_first_name,
                        legal_rep_last_name,
                        legal_rep_email,
                        legal_rep_phone,
                        subscription_plan,
                        registration_status,
                        payment_status,
                        is_active,
                        created_at
                    FROM companies_registered
                    $whereClause
                    ORDER BY $sort $order
                    LIMIT :limit OFFSET :offset";

    $latestStmt = $db->prepare($latestQuery);
    foreach ($params as $key => $value) {
        $latestStmt->bindValue($key, $value);
    }
    $latestStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $latestStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $latestStmt->execute();
    $latest_companies = $latestStmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_active to boolean
    foreach ($latest_companies as &$company) {
        $company['is_active'] = (bool)$company['is_active'];
    }

    error_log("dashboard-stats.php: About to send response");

    // Send response with flat structure (frontend expects properties at root level)
    http_response_code(200);

    $response = [
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
        'latest_companies' => $latest_companies,
        'pagination' => [
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'items_per_page' => $limit
        ]
    ];

    $json = json_encode($response);
    error_log("dashboard-stats.php: JSON encoded, length: " . strlen($json));

    echo $json;

    error_log("dashboard-stats.php: Response sent");
    exit();

} catch (PDOException $e) {
    error_log("Database error in dashboard-stats.php: " . $e->getMessage());
    Response::serverError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error in dashboard-stats.php: " . $e->getMessage());
    Response::serverError('Server error: ' . $e->getMessage());
}
