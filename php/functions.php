<?php
/**
 * Minimal Utility Functions
 * Only essential functions for authentication and basic operations
 */

/**
 * Sanitize input to prevent XSS attacks.
 *
 * @param mixed $input Raw input
 * @return mixed Sanitized string (if string), otherwise original
 */
function sanitizeInput($input) {
    if (!is_string($input)) {
        return $input;
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format.
 *
 * @param string $email Email address
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Log events.
 *
 * @param string $message
 * @param string $level info|warning|error
 * @return void
 */
function logEvent($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] [$level] $message");
}
