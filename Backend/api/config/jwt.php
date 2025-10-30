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
     *
     * @param array $company_data Company data to encode in token
     * @return string JWT token
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
     *
     * @param array $admin_data Admin data to encode in token
     * @return string JWT token
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
     *
     * @param array $user_data User data to encode in token
     * @return string JWT refresh token
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
     *
     * @param string $token JWT token
     * @return object|null Decoded token data or null if invalid
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
     * Get token from Cookie (preferred) or Authorization header (fallback)
     *
     * @return string|null Token or null if not found
     */
    public function getBearerToken() {
        // First, try to get token from httpOnly cookie (most secure)
        if (isset($_COOKIE['portal_access_token'])) {
            return $_COOKIE['portal_access_token'];
        }

        // Fallback: get from Authorization header (for API clients)
        $headers = $this->getAuthorizationHeader();

        if (!empty($headers)) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Set JWT token in httpOnly cookie
     *
     * @param string $token Access token
     * @param string $refresh_token Refresh token
     */
    public function setTokenCookies($token, $refresh_token) {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $domain = $this->getCookieDomain();

        // Set access token cookie - httpOnly, secure, SameSite
        setcookie(
            'portal_access_token',
            $token,
            [
                'expires' => time() + $this->expiration,
                'path' => '/',
                'domain' => $domain,
                'secure' => $isSecure, // true in production with HTTPS
                'httponly' => true,    // JavaScript cannot access
                'samesite' => 'Lax' // Allow cross-site for subdomain access
            ]
        );

        // Set refresh token cookie - httpOnly, secure, SameSite
        setcookie(
            'portal_refresh_token',
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
    }

    /**
     * Clear JWT cookies (for logout)
     */
    public function clearTokenCookies() {
        $domain = $this->getCookieDomain();

        setcookie('portal_access_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $domain,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        setcookie('portal_refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $domain,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Get cookie domain from environment or auto-detect
     *
     * @return string
     */
    private function getCookieDomain() {
        // Use domain from env if set, otherwise empty string for current domain
        return getenv('COOKIE_DOMAIN') ?: '';
    }

    /**
     * Get Authorization header
     *
     * @return string|null
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
