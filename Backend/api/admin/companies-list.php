<?php
/**
 * Admin - Companies List
 * Returns all registered companies with filtering and pagination
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
    // Require admin authentication
    $admin = requireAdminAuth();

    $database = new Database();
    $db = $database->getConnection();

    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

    // Build WHERE clause
    $whereConditions = [];
    $params = [];

    if (!empty($search)) {
        $whereConditions[] = "(company_name LIKE :search OR email LIKE :search OR vat_number LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
        $whereConditions[] = "registration_status = :status";
        $params[':status'] = $status;
    }

    if (!empty($payment_status)) {
        $whereConditions[] = "payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM companies_registered $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_OBJ)->total;

    // Get companies
    $query = "SELECT
                id,
                company_name,
                vat_number,
                email,
                phone,
                website,
                city,
                country,
                industry,
                employees_count,
                legal_rep_first_name,
                legal_rep_last_name,
                legal_rep_email,
                registration_status,
                payment_status,
                subscription_plan,
                subscription_start_date,
                subscription_end_date,
                tenant_schema,
                tenant_assigned_at,
                paypal_subscription_id,
                is_active,
                email_verified,
                created_at,
                last_login
              FROM companies_registered
              $whereClause
              ORDER BY created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate pagination
    $totalPages = ceil($totalRecords / $limit);

    Response::success([
        'data' => $companies,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => (int)$totalRecords,
            'per_page' => $limit
        ]
    ], 'Companies retrieved successfully');

} catch (PDOException $e) {
    error_log("Database error in companies-list.php: " . $e->getMessage());
    Response::serverError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error in companies-list.php: " . $e->getMessage());
    Response::serverError('Server error: ' . $e->getMessage());
}
