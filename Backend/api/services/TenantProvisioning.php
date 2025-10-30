<?php
/**
 * Tenant Provisioning Service
 *
 * Handles automatic tenant provisioning for new companies
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TenantPool.php';
require_once __DIR__ . '/../models/Company.php';

class TenantProvisioning {
    private $portal_db;
    private $logger;

    public function __construct($portal_db, $logger = null) {
        $this->portal_db = $portal_db;
        $this->logger = $logger;
    }

    /**
     * Provision a tenant for a company
     *
     * @param string $company_id Company ID
     * @return array Result with success status and tenant info
     */
    public function provisionTenant($company_id) {
        try {
            // Start transaction in portal DB
            $this->portal_db->beginTransaction();

            // 1. Get available tenant schema from pool
            $tenantPool = new TenantPool($this->portal_db);
            $tenant_schema = $tenantPool->getAvailableSchema();

            if (!$tenant_schema) {
                $this->portal_db->rollBack();
                return [
                    'success' => false,
                    'error' => 'No available tenant schemas. Please contact support.'
                ];
            }

            // 2. Assign schema to company in pool
            if (!$tenantPool->assignSchema($tenant_schema, $company_id)) {
                $this->portal_db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Failed to assign tenant schema'
                ];
            }

            // 3. Create tenant database if it doesn't exist
            if (!$this->createTenantDatabase($tenant_schema)) {
                $this->portal_db->rollBack();
                $tenantPool->releaseSchema($tenant_schema);
                return [
                    'success' => false,
                    'error' => 'Failed to create tenant database'
                ];
            }

            // 4. Run migrations on tenant database
            if (!$this->runTenantMigrations($tenant_schema)) {
                $this->portal_db->rollBack();
                $tenantPool->releaseSchema($tenant_schema);
                return [
                    'success' => false,
                    'error' => 'Failed to initialize tenant database structure'
                ];
            }

            // 5. Get company data
            $company = new Company($this->portal_db);
            if (!$company->findById($company_id)) {
                $this->portal_db->rollBack();
                $tenantPool->releaseSchema($tenant_schema);
                return [
                    'success' => false,
                    'error' => 'Company not found'
                ];
            }

            // 6. Create company record in tenant database
            if (!$this->createCompanyInTenant($tenant_schema, $company)) {
                $this->portal_db->rollBack();
                $tenantPool->releaseSchema($tenant_schema);
                return [
                    'success' => false,
                    'error' => 'Failed to create company in tenant database'
                ];
            }

            // 7. Create admin user in tenant database
            if (!$this->createAdminUserInTenant($tenant_schema, $company)) {
                $this->portal_db->rollBack();
                $tenantPool->releaseSchema($tenant_schema);
                return [
                    'success' => false,
                    'error' => 'Failed to create admin user in tenant database'
                ];
            }

            // 8. Update company with tenant schema in portal DB
            if (!$company->assignTenant($tenant_schema)) {
                $this->portal_db->rollBack();
                $tenantPool->releaseSchema($tenant_schema);
                return [
                    'success' => false,
                    'error' => 'Failed to update company with tenant schema'
                ];
            }

            // Commit transaction
            $this->portal_db->commit();

            // Log provisioning
            if ($this->logger) {
                $this->logger->logTenantProvisioning($company_id, $tenant_schema);
            }

            return [
                'success' => true,
                'tenant_schema' => $tenant_schema,
                'message' => 'Tenant provisioned successfully'
            ];

        } catch (Exception $e) {
            if ($this->portal_db->inTransaction()) {
                $this->portal_db->rollBack();
            }

            error_log("Tenant provisioning error: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'An error occurred during tenant provisioning: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create tenant database
     *
     * @param string $tenant_schema Schema name
     * @return bool Success status
     */
    private function createTenantDatabase($tenant_schema) {
        try {
            // Get connection to SaaS MySQL server (without specific database)
            $saas_host = getenv('SAAS_DB_HOST') ?: 'host.docker.internal';
            $saas_port = getenv('SAAS_DB_PORT') ?: '3307';
            $saas_user = getenv('SAAS_DB_USER') ?: 'candyhire_user';
            $saas_password = getenv('SAAS_DB_PASSWORD') ?: 'candyhire_pass';

            $dsn = "mysql:host={$saas_host};port={$saas_port};charset=utf8mb4";
            $conn = new PDO($dsn, $saas_user, $saas_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database if not exists
            $stmt = $conn->prepare("CREATE DATABASE IF NOT EXISTS `{$tenant_schema}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $stmt->execute();

            return true;

        } catch (PDOException $e) {
            error_log("Error creating tenant database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Run migrations on tenant database
     *
     * @param string $tenant_schema Schema name
     * @return bool Success status
     */
    private function runTenantMigrations($tenant_schema) {
        try {
            // Get tenant database connection
            $database = new Database();
            $tenant_db = $database->getTenantConnection($tenant_schema);

            if (!$tenant_db) {
                return false;
            }

            // Read and execute CandyHire migration SQL
            $migration_file = __DIR__ . '/../../migration/candyhire_schema.sql';

            if (!file_exists($migration_file)) {
                error_log("Migration file not found: {$migration_file}");
                return false;
            }

            $sql = file_get_contents($migration_file);

            // Execute SQL (split by semicolon for multiple statements)
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $tenant_db->exec($statement);
                }
            }

            return true;

        } catch (Exception $e) {
            error_log("Error running tenant migrations: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create company record in tenant database
     *
     * @param string $tenant_schema Schema name
     * @param Company $company Company object
     * @return bool Success status
     */
    private function createCompanyInTenant($tenant_schema, $company) {
        try {
            $database = new Database();
            $tenant_db = $database->getTenantConnection($tenant_schema);

            if (!$tenant_db) {
                return false;
            }

            $query = "INSERT INTO companies
                (id, name, email, phone, website, address, city, country,
                 industry, employees_count, description, type, created_at)
                VALUES
                (:id, :name, :email, :phone, :website, :address, :city, :country,
                 :industry, :employees_count, :description, 'Client', NOW())";

            $stmt = $tenant_db->prepare($query);

            $company_id = 'comp-' . uniqid();

            $stmt->bindParam(':id', $company_id);
            $stmt->bindParam(':name', $company->company_name);
            $stmt->bindParam(':email', $company->email);
            $stmt->bindParam(':phone', $company->phone);
            $stmt->bindParam(':website', $company->website);
            $stmt->bindParam(':address', $company->address);
            $stmt->bindParam(':city', $company->city);
            $stmt->bindParam(':country', $company->country);
            $stmt->bindParam(':industry', $company->industry);
            $stmt->bindParam(':employees_count', $company->employees_count);
            $stmt->bindParam(':description', $company->description);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Error creating company in tenant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create admin user in tenant database
     *
     * @param string $tenant_schema Schema name
     * @param Company $company Company object
     * @return bool Success status
     */
    private function createAdminUserInTenant($tenant_schema, $company) {
        try {
            $database = new Database();
            $tenant_db = $database->getTenantConnection($tenant_schema);

            if (!$tenant_db) {
                return false;
            }

            // First, get or create admin role
            $role_id = $this->ensureAdminRole($tenant_db);

            if (!$role_id) {
                return false;
            }

            // Create system user for company admin
            $query = "INSERT INTO system_users
                (id, email, password_hash, first_name, last_name, role_id, is_active, created_at)
                VALUES
                (:id, :email, :password_hash, :first_name, :last_name, :role_id, TRUE, NOW())";

            $stmt = $tenant_db->prepare($query);

            $user_id = 'user-' . uniqid();

            $stmt->bindParam(':id', $user_id);
            $stmt->bindParam(':email', $company->legal_rep_email);
            $stmt->bindParam(':password_hash', $company->password_hash); // Use same password
            $stmt->bindParam(':first_name', $company->legal_rep_first_name);
            $stmt->bindParam(':last_name', $company->legal_rep_last_name);
            $stmt->bindParam(':role_id', $role_id);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Error creating admin user in tenant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure admin role exists in tenant database
     *
     * @param PDO $tenant_db Tenant database connection
     * @return string|null Role ID or null on failure
     */
    private function ensureAdminRole($tenant_db) {
        try {
            // Check if admin role exists
            $query = "SELECT id FROM roles WHERE name = 'admin' LIMIT 1";
            $stmt = $tenant_db->prepare($query);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                return $row->id;
            }

            // Create admin role if not exists
            $role_id = 'role-admin';
            $query = "INSERT INTO roles (id, name, description, permissions, created_at)
                      VALUES (:id, 'admin', 'Company Administrator', '{}', NOW())";

            $stmt = $tenant_db->prepare($query);
            $stmt->bindParam(':id', $role_id);
            $stmt->execute();

            return $role_id;

        } catch (Exception $e) {
            error_log("Error ensuring admin role: " . $e->getMessage());
            return null;
        }
    }
}
