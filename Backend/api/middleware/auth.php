<?php
/**
 * Authentication Middleware
 *
 * Validates JWT token and attaches user data to request
 * Returns 401 if token is invalid or missing
 */

require_once __DIR__ . '/../config/jwt.php';

class AuthMiddleware {
    private $jwt_handler;

    public function __construct() {
        $this->jwt_handler = new JWTHandler();
    }

    /**
     * Authenticate request
     *
     * @return object|null User data from token or null if authentication fails
     */
    public function authenticate() {
        $token = $this->jwt_handler->getBearerToken();

        if (!$token) {
            $this->sendUnauthorized("Access token is missing");
            return null;
        }

        $decoded = $this->jwt_handler->validateToken($token);

        if (!$decoded) {
            $this->sendUnauthorized("Invalid or expired token");
            return null;
        }

        // Return user/company data from token
        return $decoded->data;
    }

    /**
     * Check if user is company
     *
     * @param object $user User data from token
     * @return bool
     */
    public function isCompany($user) {
        return isset($user->type) && $user->type === 'company';
    }

    /**
     * Check if user is admin
     *
     * @param object $user User data from token
     * @return bool
     */
    public function isAdmin($user) {
        return isset($user->type) && $user->type === 'admin';
    }

    /**
     * Require company authentication
     *
     * @return object Company data
     */
    public function requireCompany() {
        $user = $this->authenticate();

        if (!$user || !$this->isCompany($user)) {
            $this->sendForbidden("Company access required");
        }

        return $user;
    }

    /**
     * Require admin authentication
     *
     * @return object Admin data
     */
    public function requireAdmin() {
        $user = $this->authenticate();

        if (!$user || !$this->isAdmin($user)) {
            $this->sendForbidden("Admin access required");
        }

        return $user;
    }

    /**
     * Send unauthorized response
     *
     * @param string $message Error message
     */
    private function sendUnauthorized($message = "Unauthorized") {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }

    /**
     * Send forbidden response
     *
     * @param string $message Error message
     */
    public function sendForbidden($message = "Forbidden - Insufficient permissions") {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }
}

/**
 * Helper function to require authentication
 * Usage: $user = requireAuth();
 *
 * @return object User data from token
 */
function requireAuth() {
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();

    if (!$user) {
        exit(); // Already sent 401 response
    }

    return $user;
}

/**
 * Helper function to require company authentication
 * Usage: $company = requireCompanyAuth();
 *
 * @return object Company data from token
 */
function requireCompanyAuth() {
    $auth = new AuthMiddleware();
    return $auth->requireCompany();
}

/**
 * Helper function to require admin authentication
 * Usage: $admin = requireAdminAuth();
 *
 * @return object Admin data from token
 */
function requireAdminAuth() {
    $auth = new AuthMiddleware();
    return $auth->requireAdmin();
}
