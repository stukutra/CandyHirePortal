<?php
/**
 * Logger Utility
 *
 * Simple logging to database activity_logs table
 */

class Logger {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Log activity
     *
     * @param string $entity_type Entity type (company, admin, payment, etc.)
     * @param string $entity_id Entity ID
     * @param string $action Action performed
     * @param string $user_id User ID
     * @param string $user_type User type (company or admin)
     * @param array $metadata Additional metadata
     */
    public function logActivity($entity_type, $entity_id, $action, $user_id = null, $user_type = 'company', $metadata = []) {
        try {
            $query = "INSERT INTO activity_logs
                (entity_type, entity_id, action, user_id, user_type, ip_address, user_agent, metadata)
                VALUES (:entity_type, :entity_id, :action, :user_id, :user_type, :ip_address, :user_agent, :metadata)";

            $stmt = $this->db->prepare($query);

            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $metadata_json = !empty($metadata) ? json_encode($metadata) : null;

            $stmt->bindParam(':entity_type', $entity_type);
            $stmt->bindParam(':entity_id', $entity_id);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':user_type', $user_type);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':metadata', $metadata_json);

            $stmt->execute();

        } catch (PDOException $e) {
            error_log("Logger Error: " . $e->getMessage());
        }
    }

    /**
     * Log company registration
     */
    public function logRegistration($company_id, $company_email) {
        $this->logActivity('company', $company_id, 'registration', $company_id, 'company', [
            'email' => $company_email
        ]);
    }

    /**
     * Log company login
     */
    public function logLogin($company_id, $company_email, $success = true) {
        $action = $success ? 'login_success' : 'login_failed';
        $this->logActivity('company', $company_id, $action, $company_id, 'company', [
            'email' => $company_email
        ]);
    }

    /**
     * Log tenant provisioning
     */
    public function logTenantProvisioning($company_id, $tenant_schema) {
        $this->logActivity('company', $company_id, 'tenant_provisioned', $company_id, 'company', [
            'tenant_schema' => $tenant_schema
        ]);
    }

    /**
     * Log payment
     */
    public function logPayment($company_id, $transaction_id, $amount, $status) {
        $this->logActivity('payment', $transaction_id, 'payment_' . $status, $company_id, 'company', [
            'amount' => $amount,
            'status' => $status
        ]);
    }
}

/**
 * Get logger instance
 *
 * @param PDO $db Database connection
 * @return Logger
 */
function getLogger($db = null) {
    if (!$db) {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
    }
    return new Logger($db);
}
