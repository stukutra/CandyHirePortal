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
        // JWT_SECRET MUST be set in environment - no fallback for security
        $this->secret_key = getenv('JWT_SECRET');

        if (!$this->secret_key || strlen($this->secret_key) < 32) {
            throw new Exception('JWT_SECRET not configured or too short (min 32 chars required)');
        }

        $this->algorithm = getenv('JWT_ALGORITHM') ?: 'HS256';
        $this->expiration = (int)(getenv('JWT_EXPIRATION') ?: 86400); // 24 hours
        $this->refresh_expiration = (int)(getenv('JWT_REFRESH_EXPIRATION') ?: 604800); // 7 days
    }

    /**
     * Generate access token for company
     */
    public function generateToken($company_data) {
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

        return JWT::encode($payload, $this->secret_key, $this->algorithm);
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

        return JWT::encode($payload, $this->secret_key, $this->algorithm);
    }

    /**
     * Validate and decode token
     */
    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, $this->algorithm));
            return $decoded;
        } catch (Exception $e) {
            error_log("JWT validation error: " . $e->getMessage());
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
}
