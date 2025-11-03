<?php
/**
 * JWT Configuration and Helper Functions for Portal
 *
 * Handles JWT token generation and validation using Firebase JWT library
 * Includes tenant_schema for multi-tenancy support
 */

// Load environment variables
require_once __DIR__ . '/bootstrap.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private $secret_key;
    private $algorithm;
    private $expiration;
    private $refresh_expiration;

    public function __construct() {
        error_log("JWTHandler __construct: Starting");

        // JWT_SECRET MUST be set in environment - no fallback for security
        $this->secret_key = getenv('JWT_SECRET');

        error_log("JWTHandler __construct: JWT_SECRET from getenv: " . ($this->secret_key ? "SET (length: " . strlen($this->secret_key) . ")" : "NOT SET"));

        // Try $_ENV as fallback
        if (!$this->secret_key && isset($_ENV['JWT_SECRET'])) {
            $this->secret_key = $_ENV['JWT_SECRET'];
            error_log("JWTHandler __construct: JWT_SECRET from \$_ENV: " . ($this->secret_key ? "SET (length: " . strlen($this->secret_key) . ")" : "NOT SET"));
        }

        if (!$this->secret_key || strlen($this->secret_key) < 32) {
            error_log("JWTHandler __construct: ERROR - JWT_SECRET not configured or too short");
            throw new Exception('JWT_SECRET not configured or too short (min 32 chars required)');
        }

        $this->algorithm = getenv('JWT_ALGORITHM') ?: 'HS256';
        $this->expiration = (int)(getenv('JWT_EXPIRATION') ?: 86400); // 24 hours
        $this->refresh_expiration = (int)(getenv('JWT_REFRESH_EXPIRATION') ?: 604800); // 7 days

        error_log("JWTHandler __construct: Completed successfully");
    }

    /**
     * Generate access token for company
     */
    public function generateToken($company_data) {
        error_log("JWTHandler generateToken: Starting for company: " . ($company_data['id'] ?? 'N/A'));

        $issued_at = time();
        $expiration_time = $issued_at + $this->expiration;

        $payload = [
            'iat' => $issued_at,
            'exp' => $expiration_time,
            'iss' => 'candyhire-portal-api',
            'data' => [
                'id' => $company_data['id'],
                'email' => $company_data['email'],
                'company_name' => $company_data['company_name'] ?? null,
                'tenant_schema' => $company_data['tenant_schema'] ?? null,
                'type' => 'company'
            ]
        ];

        error_log("JWTHandler generateToken: Payload prepared, encoding...");
        error_log("JWTHandler generateToken: Payload JSON: " . json_encode($payload));
        error_log("JWTHandler generateToken: Secret key type: " . gettype($this->secret_key));
        error_log("JWTHandler generateToken: Secret key length: " . strlen($this->secret_key));
        error_log("JWTHandler generateToken: Algorithm: " . $this->algorithm);
        error_log("JWTHandler generateToken: About to call JWT::encode()...");

        try {
            $token = JWT::encode($payload, $this->secret_key, $this->algorithm);
            error_log("JWTHandler generateToken: JWT::encode() returned");
            error_log("JWTHandler generateToken: Token generated successfully (length: " . strlen($token) . ")");
        } catch (\Exception $e) {
            error_log("JWTHandler generateToken: EXCEPTION during JWT::encode()");
            error_log("JWTHandler generateToken: Exception class: " . get_class($e));
            error_log("JWTHandler generateToken: Exception message: " . $e->getMessage());
            error_log("JWTHandler generateToken: Exception trace: " . $e->getTraceAsString());
            throw $e; // Re-throw
        } catch (\Throwable $t) {
            error_log("JWTHandler generateToken: THROWABLE during JWT::encode()");
            error_log("JWTHandler generateToken: Throwable class: " . get_class($t));
            error_log("JWTHandler generateToken: Throwable message: " . $t->getMessage());
            error_log("JWTHandler generateToken: Throwable trace: " . $t->getTraceAsString());
            throw $t; // Re-throw
        }

        error_log("JWTHandler generateToken: Returning token");
        return $token;
    }

    /**
     * Generate access token for admin user
     */
    public function generateAdminToken($admin_data) {
        $issued_at = time();
        $expiration_time = $issued_at + $this->expiration;

        $payload = [
            'iat' => $issued_at,
            'exp' => $expiration_time,
            'iss' => 'candyhire-portal-api',
            'data' => [
                'id' => $admin_data['id'],
                'email' => $admin_data['email'],
                'username' => $admin_data['username'] ?? null,
                'role' => $admin_data['role'] ?? 'admin',
                'type' => 'admin'
            ]
        ];

        return JWT::encode($payload, $this->secret_key, $this->algorithm);
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken($user_data) {
        error_log("JWTHandler generateRefreshToken: Starting for user: " . ($user_data['id'] ?? 'N/A'));

        $issued_at = time();
        $expiration_time = $issued_at + $this->refresh_expiration;

        $payload = [
            'iat' => $issued_at,
            'exp' => $expiration_time,
            'iss' => 'candyhire-portal-api',
            'type' => 'refresh',
            'data' => [
                'id' => $user_data['id']
            ]
        ];

        error_log("JWTHandler generateRefreshToken: Encoding...");

        try {
            $token = JWT::encode($payload, $this->secret_key, $this->algorithm);
            error_log("JWTHandler generateRefreshToken: Token generated successfully (length: " . strlen($token) . ")");
        } catch (\Exception $e) {
            error_log("JWTHandler generateRefreshToken: EXCEPTION during JWT::encode()");
            error_log("JWTHandler generateRefreshToken: Exception class: " . get_class($e));
            error_log("JWTHandler generateRefreshToken: Exception message: " . $e->getMessage());
            error_log("JWTHandler generateRefreshToken: Exception trace: " . $e->getTraceAsString());
            throw $e; // Re-throw
        }

        return $token;
    }

    /**
     * Validate and decode token
     */
    public function validateToken($token) {
        error_log("validateToken: Starting, token length: " . strlen($token));
        error_log("validateToken: Full token: " . $token);
        error_log("validateToken: Secret length: " . strlen($this->secret_key));

        try {
            error_log("validateToken: About to decode JWT");
            $decoded = JWT::decode($token, new Key($this->secret_key, $this->algorithm));
            error_log("validateToken: JWT decoded successfully");
            return $decoded;
        } catch (Exception $e) {
            error_log("JWT validation error: " . $e->getMessage());
            error_log("JWT validation error class: " . get_class($e));
            error_log("JWT validation error trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Get token from Cookie or Authorization header
     */
    public function getBearerToken() {
        // First, try to get token from httpOnly cookie
        if (isset($_COOKIE['portal_access_token'])) {
            return $_COOKIE['portal_access_token'];
        }

        // Fallback: get from Authorization header
        $headers = $this->getAuthorizationHeader();

        if (!empty($headers)) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get Authorization header
     */
    private function getAuthorizationHeader() {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );

            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }

    /**
     * Validate token for admin access
     */
    public function validateAdminToken($token) {
        $decoded = $this->validateToken($token);

        if (!$decoded || !isset($decoded->data->type) || $decoded->data->type !== 'admin') {
            return null;
        }

        return $decoded;
    }

    /**
     * Validate token for company access
     */
    public function validateCompanyToken($token) {
        $decoded = $this->validateToken($token);

        if (!$decoded || !isset($decoded->data->type) || $decoded->data->type !== 'company') {
            return null;
        }

        return $decoded;
    }

    /**
     * Set JWT token in httpOnly cookie (for SSO)
     * Works across Portal and SaaS subdomains
     */
    public function setTokenCookies($token, $refresh_token) {
        error_log("JWTHandler setTokenCookies: Starting");

        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $domain = $this->getCookieDomain();

        error_log("JWTHandler setTokenCookies: isSecure=" . ($isSecure ? 'YES' : 'NO') . ", domain=" . ($domain ?: 'EMPTY'));

        // Set access token cookie
        setcookie(
            'candyhire_access_token',
            $token,
            [
                'expires' => time() + $this->expiration,
                'path' => '/',
                'domain' => $domain,
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax' // Allow cross-subdomain (www -> app)
            ]
        );

        error_log("JWTHandler setTokenCookies: Access token cookie set");

        // Set refresh token cookie
        setcookie(
            'candyhire_refresh_token',
            $refresh_token,
            [
                'expires' => time() + $this->refresh_expiration,
                'path' => '/',
                'domain' => $domain,
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        error_log("JWTHandler setTokenCookies: Refresh token cookie set - Completed successfully");
    }

    /**
     * Clear JWT cookies (for logout)
     */
    public function clearTokenCookies() {
        $domain = $this->getCookieDomain();

        setcookie('candyhire_access_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $domain,
            'httponly' => true
        ]);

        setcookie('candyhire_refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $domain,
            'httponly' => true
        ]);
    }

    /**
     * Get cookie domain based on environment
     * Returns empty string for localhost, .candyhire.cloud for production
     */
    private function getCookieDomain() {
        $is_production = getenv('APP_ENV') === 'production';

        if ($is_production) {
            // Use .candyhire.cloud to share cookies between www and app subdomains
            return '.candyhire.cloud';
        }

        // Development: use empty string (current domain only)
        return '';
    }
}
