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
     * Tenant databases are in the same MySQL server as Portal
     *
     * @param string $tenant_schema Tenant schema name
     * @return PDO|null
     */
    public function getTenantConnection($tenant_schema) {
        // Tenant databases are in the same MySQL server as Portal
        $host = getenv('DB_HOST') ?: 'portal-mysql';
        $username = getenv('DB_ROOT_USER') ?: 'root';
        $password = getenv('DB_ROOT_PASSWORD') ?: 'candyhire_portal_root_pass';

        try {
            $dsn = "mysql:host={$host};dbname={$tenant_schema};charset=utf8mb4";
            $conn = new PDO($dsn, $username, $password);

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            error_log("Tenant connection established to: $tenant_schema");
            return $conn;

        } catch(PDOException $e) {
            error_log("Tenant Database Connection Error for $tenant_schema: " . $e->getMessage());
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
