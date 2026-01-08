<?php
/**
 * Utility Functions
 * Common functions used throughout the authentication system
 */

/**
 * Read and decode a JSON file
 * 
 * @param string $filepath Path to JSON file
 * @return array Decoded JSON data or empty array on failure
 */
function readJsonFile($filepath) {
    if (!file_exists($filepath)) {
        logEvent("JSON file not found: $filepath", 'error');
        return [];
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        logEvent("Failed to read JSON file: $filepath", 'error');
        return [];
    }
    
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logEvent("JSON decode error: " . json_last_error_msg(), 'error');
        return [];
    }
    
    return $data;
}

/**
 * Write data to a JSON file
 * Note: No file locking as per specification
 * 
 * @param string $filepath Path to JSON file
 * @param array $data Data to encode and write
 * @return bool True on success, false on failure
 */
function writeJsonFile($filepath, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        logEvent("JSON encode error: " . json_last_error_msg(), 'error');
        return false;
    }
    
    $result = file_put_contents($filepath, $json);
    if ($result === false) {
        logEvent("Failed to write JSON file: $filepath", 'error');
        return false;
    }
    
    return true;
}

/**
 * Generate a unique user ID
 * 
 * @return string Unique identifier
 */
function generateUniqueId() {
    return uniqid('user_', true);
}

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $input Raw input string
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    if (!is_string($input)) {
        return $input;
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get current logged-in user from session
 * 
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'google_id' => $_SESSION['google_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'profile_picture' => $_SESSION['profile_picture'] ?? null
    ];
}

/**
 * Log authentication events
 * 
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 * @return void
 */
function logEvent($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message";
    error_log($logMessage);
}

/**
 * Find user by Google ID in users.json
 * 
 * @param string $googleId Google user ID
 * @return array|null User data or null if not found
 */
function findUserByGoogleId($googleId) {
    $data = readJsonFile(USERS_JSON_PATH);
    
    if (!isset($data['users']) || !is_array($data['users'])) {
        return null;
    }
    
    foreach ($data['users'] as $user) {
        if (isset($user['google_id']) && $user['google_id'] === $googleId) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Find user by email in users.json
 * 
 * @param string $email User email address
 * @return array|null User data or null if not found
 */
function findUserByEmail($email) {
    $data = readJsonFile(USERS_JSON_PATH);
    
    if (!isset($data['users']) || !is_array($data['users'])) {
        return null;
    }
    
    foreach ($data['users'] as $user) {
        if (isset($user['email']) && $user['email'] === $email) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Create a new user in users.json
 * 
 * @param array $userData User data from Google OAuth
 * @return array|null Created user data or null on failure
 */
function createUser($userData) {
    $data = readJsonFile(USERS_JSON_PATH);
    
    if (!isset($data['users'])) {
        $data['users'] = [];
    }
    
    $newUser = [
        'id' => generateUniqueId(),
        'google_id' => sanitizeInput($userData['google_id']),
        'name' => sanitizeInput($userData['name']),
        'email' => sanitizeInput($userData['email']),
        'profile_picture' => sanitizeInput($userData['profile_picture']),
        'timezone' => 'UTC',
        'created_at' => date('c'),
        'last_login' => date('c'),
        'preferences' => [
            'notifications_enabled' => true,
            'default_view' => 'month'
        ]
    ];
    
    $data['users'][] = $newUser;
    
    if (writeJsonFile(USERS_JSON_PATH, $data)) {
        logEvent("New user created: {$newUser['email']}", 'info');
        return $newUser;
    }
    
    return null;
}

/**
 * Update user's last login timestamp
 * 
 * @param string $googleId Google user ID
 * @return bool True on success, false on failure
 */
function updateLastLogin($googleId) {
    $data = readJsonFile(USERS_JSON_PATH);
    
    if (!isset($data['users']) || !is_array($data['users'])) {
        return false;
    }
    
    $updated = false;
    foreach ($data['users'] as &$user) {
        if (isset($user['google_id']) && $user['google_id'] === $googleId) {
            $user['last_login'] = date('c');
            $updated = true;
            break;
        }
    }
    
    if ($updated && writeJsonFile(USERS_JSON_PATH, $data)) {
        logEvent("Updated last login for Google ID: $googleId", 'info');
        return true;
    }
    
    return false;
}

/**
 * Send JSON response and exit
 * 
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 * @return void
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo json_encode($data);
    exit;
}
