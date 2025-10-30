<?php
/**
 * TenantPool Model
 *
 * Handles tenant_pool table operations for multi-tenancy
 */

class TenantPool {
    private $db;

    public $id;
    public $schema_name;
    public $is_available;
    public $company_id;
    public $assigned_at;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get first available tenant schema
     *
     * @return string|null Schema name or null if none available
     */
    public function getAvailableSchema() {
        $query = "SELECT * FROM tenant_pool
                  WHERE is_available = TRUE
                  ORDER BY id ASC
                  LIMIT 1
                  FOR UPDATE"; // Lock row to prevent race conditions

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                $this->mapFromRow($row);
                return $this->schema_name;
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error getting available schema: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Assign schema to company
     *
     * @param string $schema_name Schema name to assign
     * @param string $company_id Company ID
     * @return bool Success status
     */
    public function assignSchema($schema_name, $company_id) {
        $query = "UPDATE tenant_pool
                  SET is_available = FALSE,
                      company_id = :company_id,
                      assigned_at = NOW()
                  WHERE schema_name = :schema_name
                  AND is_available = TRUE";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':schema_name', $schema_name);
            $stmt->bindParam(':company_id', $company_id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error assigning schema: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Release schema (make it available again)
     *
     * @param string $schema_name Schema name to release
     * @return bool Success status
     */
    public function releaseSchema($schema_name) {
        $query = "UPDATE tenant_pool
                  SET is_available = TRUE,
                      company_id = NULL,
                      assigned_at = NULL
                  WHERE schema_name = :schema_name";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':schema_name', $schema_name);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error releasing schema: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all tenant schemas with status
     *
     * @return array List of all tenant schemas
     */
    public function getAll() {
        $query = "SELECT tp.*, cr.company_name, cr.email
                  FROM tenant_pool tp
                  LEFT JOIN companies_registered cr ON tp.company_id = cr.id
                  ORDER BY tp.id ASC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all schemas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get schema by company ID
     *
     * @param string $company_id Company ID
     * @return string|null Schema name or null if not found
     */
    public function getSchemaByCompanyId($company_id) {
        $query = "SELECT schema_name FROM tenant_pool
                  WHERE company_id = :company_id
                  LIMIT 1";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':company_id', $company_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                return $row->schema_name;
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error getting schema by company ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Count available schemas
     *
     * @return int Number of available schemas
     */
    public function countAvailable() {
        $query = "SELECT COUNT(*) as count FROM tenant_pool WHERE is_available = TRUE";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch();

            return (int)$row->count;
        } catch (PDOException $e) {
            error_log("Error counting available schemas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Map database row to object properties
     */
    private function mapFromRow($row) {
        $this->id = $row->id;
        $this->schema_name = $row->schema_name;
        $this->is_available = $row->is_available;
        $this->company_id = $row->company_id;
        $this->assigned_at = $row->assigned_at;
        $this->created_at = $row->created_at;
        $this->updated_at = $row->updated_at;
    }
}
