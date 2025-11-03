<?php
/**
 * Check if VAT number already exists
 * Public endpoint - No authentication required
 *
 * POST /api/auth/check-vat.php
 * Body: { "vat_number": "12345678901" }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->vat_number) || empty($data->vat_number)) {
    Response::error('VAT number is required', 400);
}

$vat_number = trim($data->vat_number);

if (strlen($vat_number) < 5) {
    Response::error('Invalid VAT number format', 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if VAT number exists in companies_registered
    $query = "SELECT id, company_name FROM companies_registered WHERE vat_number = :vat_number LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vat_number', $vat_number);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        // VAT exists
        Response::success([
            'exists' => true,
            'message' => 'This VAT number is already registered',
            'company_name' => $company['company_name']
        ]);
    } else {
        // VAT available
        Response::success([
            'exists' => false,
            'message' => 'VAT number available'
        ]);
    }

} catch (Exception $e) {
    error_log("Error checking VAT: " . $e->getMessage());
    Response::serverError('Server error occurred');
}
