<?php
/**
 * Logout Handler
 * Destroys user session and redirects to login page
 */

require_once __DIR__ . '/config.php';

// Start session
session_start();

// Log the logout event before destroying session
$email = $_SESSION['email'] ?? 'unknown';
logEvent("User logged out: $email", 'info');

// Unset all session variables
$_SESSION = [];

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Redirect to index page for regular requests
header('Location: /index.html');
exit;
