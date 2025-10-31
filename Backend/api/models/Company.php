<?php
/**
 * Company Model
 *
 * Handles companies_registered table operations
 */

class Company {
    private $db;

    public $id;
    public $company_name;
    public $vat_number;
    public $sdi_code;
    public $email;
    public $phone;
    public $website;
    public $address;
    public $city;
    public $postal_code;
    public $province;
    public $country;
    public $country_code;
    public $industry;
    public $employees_count;
    public $description;
    public $legal_rep_first_name;
    public $legal_rep_last_name;
    public $legal_rep_email;
    public $legal_rep_phone;
    public $password_hash;
    public $registration_status;
    public $payment_status;
    public $subscription_plan;
    public $subscription_start_date;
    public $subscription_end_date;
    public $tenant_schema;
    public $tenant_assigned_at;
    public $paypal_subscription_id;
    public $paypal_payer_id;
    public $is_active;
    public $email_verified;
    public $terms_accepted;
    public $privacy_accepted;
    public $created_at;
    public $updated_at;
    public $last_login;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Generate unique company ID
     */
    public static function generateId() {
        return 'comp-' . uniqid() . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Create new company registration
     */
    public function create() {
        $query = "INSERT INTO companies_registered
            (id, company_name, vat_number, sdi_code, email, phone, website,
             address, city, postal_code, province, country, country_code,
             industry, employees_count, description,
             legal_rep_first_name, legal_rep_last_name, legal_rep_email, legal_rep_phone,
             password_hash, registration_status, payment_status,
             subscription_plan, terms_accepted, privacy_accepted)
            VALUES
            (:id, :company_name, :vat_number, :sdi_code, :email, :phone, :website,
             :address, :city, :postal_code, :province, :country, :country_code,
             :industry, :employees_count, :description,
             :legal_rep_first_name, :legal_rep_last_name, :legal_rep_email, :legal_rep_phone,
             :password_hash, :registration_status, :payment_status,
             :subscription_plan, :terms_accepted, :privacy_accepted)";

        $stmt = $this->db->prepare($query);

        // Generate ID if not set
        if (!$this->id) {
            $this->id = self::generateId();
        }

        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':company_name', $this->company_name);
        $stmt->bindParam(':vat_number', $this->vat_number);
        $stmt->bindParam(':sdi_code', $this->sdi_code);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':website', $this->website);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':postal_code', $this->postal_code);
        $stmt->bindParam(':province', $this->province);
        $stmt->bindParam(':country', $this->country);
        $stmt->bindParam(':country_code', $this->country_code);
        $stmt->bindParam(':industry', $this->industry);
        $stmt->bindParam(':employees_count', $this->employees_count);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':legal_rep_first_name', $this->legal_rep_first_name);
        $stmt->bindParam(':legal_rep_last_name', $this->legal_rep_last_name);
        $stmt->bindParam(':legal_rep_email', $this->legal_rep_email);
        $stmt->bindParam(':legal_rep_phone', $this->legal_rep_phone);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':registration_status', $this->registration_status);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->bindParam(':subscription_plan', $this->subscription_plan);
        $stmt->bindParam(':terms_accepted', $this->terms_accepted);
        $stmt->bindParam(':privacy_accepted', $this->privacy_accepted);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * Find company by email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM companies_registered WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $this->mapFromRow($row);
            return true;
        }

        return false;
    }

    /**
     * Find company by ID
     */
    public function findById($id) {
        $query = "SELECT * FROM companies_registered WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $this->mapFromRow($row);
            return true;
        }

        return false;
    }

    /**
     * Verify password
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Update last login
     */
    public function updateLastLogin() {
        $query = "UPDATE companies_registered SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    /**
     * Assign tenant schema
     */
    public function assignTenant($tenant_schema) {
        $query = "UPDATE companies_registered
                  SET tenant_schema = :tenant_schema,
                      tenant_assigned_at = NOW(),
                      is_active = TRUE
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':tenant_schema', $tenant_schema);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($status, $paypal_subscription_id = null, $paypal_payer_id = null) {
        $query = "UPDATE companies_registered
                  SET payment_status = :status,
                      paypal_subscription_id = :paypal_subscription_id,
                      paypal_payer_id = :paypal_payer_id,
                      registration_status = 'payment_completed'
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':paypal_subscription_id', $paypal_subscription_id);
        $stmt->bindParam(':paypal_payer_id', $paypal_payer_id);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Get all companies with filters
     */
    public function getAll($filters = []) {
        $query = "SELECT * FROM companies_registered WHERE 1=1";
        $params = [];

        if (isset($filters['registration_status'])) {
            $query .= " AND registration_status = :registration_status";
            $params[':registration_status'] = $filters['registration_status'];
        }

        if (isset($filters['payment_status'])) {
            $query .= " AND payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Map database row to object properties
     */
    private function mapFromRow($row) {
        $this->id = $row->id;
        $this->company_name = $row->company_name;
        $this->vat_number = $row->vat_number;
        $this->sdi_code = $row->sdi_code ?? null;
        $this->email = $row->email;
        $this->phone = $row->phone;
        $this->website = $row->website;
        $this->address = $row->address;
        $this->city = $row->city;
        $this->postal_code = $row->postal_code;
        $this->province = $row->province;
        $this->country = $row->country;
        $this->country_code = $row->country_code ?? null;
        $this->industry = $row->industry;
        $this->employees_count = $row->employees_count;
        $this->description = $row->description;
        $this->legal_rep_first_name = $row->legal_rep_first_name;
        $this->legal_rep_last_name = $row->legal_rep_last_name;
        $this->legal_rep_email = $row->legal_rep_email;
        $this->legal_rep_phone = $row->legal_rep_phone;
        $this->password_hash = $row->password_hash;
        $this->registration_status = $row->registration_status;
        $this->payment_status = $row->payment_status;
        $this->subscription_plan = $row->subscription_plan;
        $this->subscription_start_date = $row->subscription_start_date;
        $this->subscription_end_date = $row->subscription_end_date;
        $this->tenant_schema = $row->tenant_schema;
        $this->tenant_assigned_at = $row->tenant_assigned_at;
        $this->paypal_subscription_id = $row->paypal_subscription_id;
        $this->paypal_payer_id = $row->paypal_payer_id;
        $this->is_active = $row->is_active;
        $this->email_verified = $row->email_verified;
        $this->terms_accepted = $row->terms_accepted;
        $this->privacy_accepted = $row->privacy_accepted;
        $this->created_at = $row->created_at;
        $this->updated_at = $row->updated_at;
        $this->last_login = $row->last_login;
    }
}
