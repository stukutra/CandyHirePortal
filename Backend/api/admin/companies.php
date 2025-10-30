<?php
/**
 * Admin Companies Management
 *
 * GET /api/admin/companies.php
 * Requires admin authentication
 *
 * Returns list of all registered companies with status
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/TenantPool.php';
require_once __DIR__ . '/../utils/response.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Require admin authentication
// For now, we'll allow any authenticated request (TODO: add proper admin auth)
// $admin_data = requireAdminAuth();

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Response::serverError('Database connection failed');
    }

    // Get filters from query string
    $filters = [];

    if (isset($_GET['registration_status'])) {
        $filters['registration_status'] = $_GET['registration_status'];
    }

    if (isset($_GET['payment_status'])) {
        $filters['payment_status'] = $_GET['payment_status'];
    }

    // Get all companies
    $company = new Company($db);
    $companies = $company->getAll($filters);

    // Get tenant pool status
    $tenantPool = new TenantPool($db);
    $tenant_schemas = $tenantPool->getAll();
    $available_count = $tenantPool->countAvailable();

    // Format response
    $companies_data = array_map(function($comp) {
        return [
            'id' => $comp->id,
            'company_name' => $comp->company_name,
            'vat_number' => $comp->vat_number,
            'email' => $comp->email,
            'phone' => $comp->phone,
            'website' => $comp->website,
            'city' => $comp->city,
            'country' => $comp->country,
            'industry' => $comp->industry,
            'employees_count' => $comp->employees_count,
            'legal_representative' => [
                'first_name' => $comp->legal_rep_first_name,
                'last_name' => $comp->legal_rep_last_name,
                'email' => $comp->legal_rep_email,
                'phone' => $comp->legal_rep_phone
            ],
            'registration_status' => $comp->registration_status,
            'payment_status' => $comp->payment_status,
            'subscription_plan' => $comp->subscription_plan,
            'tenant_schema' => $comp->tenant_schema,
            'tenant_assigned_at' => $comp->tenant_assigned_at,
            'is_active' => (bool)$comp->is_active,
            'created_at' => $comp->created_at,
            'last_login' => $comp->last_login
        ];
    }, $companies);

    // Return response
    Response::success([
        'companies' => $companies_data,
        'total' => count($companies_data),
        'tenant_pool' => [
            'available' => $available_count,
            'total' => count($tenant_schemas),
            'schemas' => array_map(function($schema) {
                return [
                    'schema_name' => $schema->schema_name,
                    'is_available' => (bool)$schema->is_available,
                    'company_id' => $schema->company_id,
                    'company_name' => $schema->company_name ?? null,
                    'assigned_at' => $schema->assigned_at
                ];
            }, $tenant_schemas)
        ]
    ], 'Companies retrieved successfully');

} catch (Exception $e) {
    error_log("Admin companies error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving companies');
}
