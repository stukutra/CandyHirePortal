<?php
/**
 * Database Configuration and Connection Handler
 */

require_once __DIR__ . '/bootstrap.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct($db_name = null) {
        $this->host = getenv('DB_HOST') ?: 'portal-mysql';
        $this->db_name = $db_name ?: getenv('DB_NAME') ?: 'CandyHirePortal';
        $this->username = getenv('DB_USER') ?: 'candyhire_portal_user';
        $this->password = getenv('DB_PASSWORD') ?: 'candyhire_portal_pass';
    }

    /**
     * Get database connection
     *
     * @return PDO|null Database connection or null on failure
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Set PDO attributes
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            return null;
        }

        return $this->conn;
    }

    /**
     * Get connection to a specific tenant database
     *
     * @param string $tenant_schema Tenant schema name
     * @return PDO|null
     */
    public function getTenantConnection($tenant_schema) {
        $saas_host = getenv('SAAS_DB_HOST') ?: 'host.docker.internal';
        $saas_port = getenv('SAAS_DB_PORT') ?: '3307';
        $saas_user = getenv('SAAS_DB_USER') ?: 'candyhire_user';
        $saas_password = getenv('SAAS_DB_PASSWORD') ?: 'candyhire_pass';

        try {
            $dsn = "mysql:host={$saas_host};port={$saas_port};dbname={$tenant_schema};charset=utf8mb4";
            $conn = new PDO($dsn, $saas_user, $saas_password);

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            return $conn;

        } catch(PDOException $e) {
            error_log("Tenant Database Connection Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Close connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
