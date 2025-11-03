<?php
/**
 * Tenant Initializer
 *
 * Handles tenant database initialization after payment:
 * - Creates default Super Admin role
 * - Inserts company data
 * - Creates first admin user (legal representative)
 */

require_once __DIR__ . '/../config/database.php';

class TenantInitializer {
    private $tenant_db;
    private $tenant_id;
    private $tenant_schema;

    /**
     * Initialize tenant database connection
     *
     * @param string $tenant_schema Database schema name (e.g., 'candyhire_tenant_1')
     * @param string $tenant_id Tenant ID (e.g., '1')
     */
    public function __construct($tenant_schema, $tenant_id) {
        $this->tenant_schema = $tenant_schema;
        $this->tenant_id = $tenant_id;

        // Connect to tenant database
        $database = new Database();
        $this->tenant_db = $database->getTenantConnection($tenant_schema);

        if (!$this->tenant_db) {
            throw new Exception("Failed to connect to tenant database: $tenant_schema");
        }
    }

    /**
     * Initialize tenant with company data and first admin user
     *
     * @param array $company_data Company data from Portal DB
     * @return array ['user_id' => string, 'tenant_id' => string]
     */
    public function initializeTenant($company_data) {
        try {
            error_log("TenantInit: Starting tenant initialization for " . $this->tenant_schema);

            // Check if tenant is already initialized (has any users)
            $stmt = $this->tenant_db->prepare("SELECT COUNT(*) as count FROM system_users WHERE tenant_id = ?");
            $stmt->execute([$this->tenant_id]);
            $result = $stmt->fetch();

            if ($result && $result['count'] > 0) {
                error_log("TenantInit: Tenant already initialized, fetching existing admin user");

                // Get existing admin user
                $stmt = $this->tenant_db->prepare("
                    SELECT u.id as user_id, u.role_id, r.id as role_id
                    FROM system_users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.tenant_id = ? AND u.email = ?
                    LIMIT 1
                ");
                $stmt->execute([$this->tenant_id, $company_data['legal_rep_email']]);
                $existing = $stmt->fetch();

                if ($existing) {
                    error_log("TenantInit: Found existing admin user - ID: " . $existing['user_id']);
                    return [
                        'user_id' => $existing['user_id'],
                        'tenant_id' => $this->tenant_id,
                        'role_id' => $existing['role_id']
                    ];
                }
            }

            $this->tenant_db->beginTransaction();

            // Step 1: Create Super Admin role
            $role_id = $this->createSuperAdminRole();
            error_log("TenantInit: Super Admin role created with ID: $role_id");

            // Step 2: Insert company data
            $company_id = $this->insertCompany($company_data);
            error_log("TenantInit: Company inserted with ID: $company_id");

            // Step 3: Create first admin user (legal representative)
            $user_id = $this->createFirstAdmin($company_data, $role_id);
            error_log("TenantInit: First admin user created with ID: $user_id");

            $this->tenant_db->commit();
            error_log("TenantInit: Tenant initialization completed successfully");

            return [
                'user_id' => $user_id,
                'tenant_id' => $this->tenant_id,
                'company_id' => $company_id,
                'role_id' => $role_id
            ];

        } catch (Exception $e) {
            if ($this->tenant_db->inTransaction()) {
                $this->tenant_db->rollBack();
            }
            error_log("TenantInit ERROR: " . $e->getMessage());
            throw new Exception("Tenant initialization failed: " . $e->getMessage());
        }
    }

    /**
     * Create Super Admin role with all permissions
     */
    private function createSuperAdminRole() {
        $role_id = 'role-' . uniqid();

        $stmt = $this->tenant_db->prepare("
            INSERT INTO roles (id, tenant_id, name, description, permissions)
            VALUES (?, ?, ?, ?, ?)
        ");

        $permissions = json_encode([
            'users' => ['create', 'read', 'update', 'delete'],
            'companies' => ['create', 'read', 'update', 'delete'],
            'candidates' => ['create', 'read', 'update', 'delete'],
            'jobs' => ['create', 'read', 'update', 'delete'],
            'applications' => ['create', 'read', 'update', 'delete'],
            'interviews' => ['create', 'read', 'update', 'delete'],
            'documents' => ['create', 'read', 'update', 'delete'],
            'settings' => ['read', 'update'],
            'reports' => ['read'],
            'all' => true // Super admin has all permissions
        ]);

        $stmt->execute([
            $role_id,
            $this->tenant_id,
            'Super Admin',
            'Full system access with all permissions',
            $permissions
        ]);

        return $role_id;
    }

    /**
     * Insert company data into tenant database
     */
    private function insertCompany($company_data) {
        $company_id = 'comp-' . uniqid();

        $stmt = $this->tenant_db->prepare("
            INSERT INTO companies (
                id, tenant_id, name, email, phone, website,
                address, city, country, industry, employees_count,
                description, type, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Client', NOW())
        ");

        $stmt->execute([
            $company_id,
            $this->tenant_id,
            $company_data['company_name'],
            $company_data['email'],
            $company_data['phone'] ?? null,
            $company_data['website'] ?? null,
            $company_data['address'] ?? null,
            $company_data['city'] ?? null,
            $company_data['country'] ?? 'Italy',
            $company_data['industry'] ?? 'General',
            $company_data['employees_count'] ?? null,
            $company_data['description'] ?? null
        ]);

        return $company_id;
    }

    /**
     * Create first admin user (legal representative)
     */
    private function createFirstAdmin($company_data, $role_id) {
        $user_id = 'user-' . uniqid();

        $stmt = $this->tenant_db->prepare("
            INSERT INTO system_users (
                id, tenant_id, email, password_hash,
                first_name, last_name, username,
                role_id, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        // Username is first initial + last name (e.g., "jsmith")
        $username = strtolower(
            substr($company_data['legal_rep_first_name'], 0, 1) .
            $company_data['legal_rep_last_name']
        );
        // Remove spaces and special chars
        $username = preg_replace('/[^a-z0-9]/', '', $username);

        $stmt->execute([
            $user_id,
            $this->tenant_id,
            $company_data['legal_rep_email'],
            $company_data['password_hash'], // Already hashed during registration
            $company_data['legal_rep_first_name'],
            $company_data['legal_rep_last_name'],
            $username,
            $role_id
        ]);

        return $user_id;
    }
}
