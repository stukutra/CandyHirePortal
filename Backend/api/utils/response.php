<?php
/**
 * Response Utilities
 *
 * Helper class for consistent API responses
 */

class Response {
    /**
     * Send success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Send error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     */
    public static function error($message = 'An error occurred', $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Send validation error response
     *
     * @param array $errors Validation errors
     * @param string $message General error message
     */
    public static function validationError($errors, $message = 'Validation failed') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Send unauthorized response
     *
     * @param string $message Error message
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     *
     * @param string $message Error message
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }

    /**
     * Send not found response
     *
     * @param string $message Error message
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Send server error response
     *
     * @param string $message Error message
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
}
