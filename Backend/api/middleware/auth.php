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
        error_log("authenticate: Getting bearer token");
        $token = $this->jwt_handler->getBearerToken();
        error_log("authenticate: Token: " . ($token ? substr($token, 0, 20) . '...' : 'NULL'));

        if (!$token) {
            error_log("authenticate: No token, sending 401");
            $this->sendUnauthorized("Access token is missing");
            return null;
        }

        error_log("authenticate: Validating token");
        $decoded = $this->jwt_handler->validateToken($token);
        error_log("authenticate: Decoded: " . ($decoded ? 'YES' : 'NO'));

        if (!$decoded) {
            error_log("authenticate: Invalid token, sending 401");
            $this->sendUnauthorized("Invalid or expired token");
            return null;
        }

        error_log("authenticate: Returning user data");
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
        error_log("requireAdmin: Calling authenticate");
        $user = $this->authenticate();
        error_log("requireAdmin: User returned: " . ($user ? json_encode($user) : 'NULL'));

        if (!$user) {
            error_log("requireAdmin: User is null, sending forbidden");
            $this->sendForbidden("Admin access required - no user");
        }

        if (!$this->isAdmin($user)) {
            error_log("requireAdmin: User is not admin, type: " . (isset($user->type) ? $user->type : 'NO TYPE'));
            $this->sendForbidden("Admin access required - not admin");
        }

        error_log("requireAdmin: Success, returning user");
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
    error_log("requireAdminAuth: Called");
    $auth = new AuthMiddleware();
    $result = $auth->requireAdmin();
    error_log("requireAdminAuth: Returning admin: " . ($result ? $result->email : 'NULL'));
    return $result;
}
