<?php
/**
 * Session Check Endpoint
 * AJAX endpoint to check if user is authenticated
 * Returns JSON with authentication status and user data
 */

require_once __DIR__ . '/config.php';

// Set headers for JSON response and prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // User is authenticated
    $response = [
        'authenticated' => true,
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'profile_picture' => $_SESSION['profile_picture'] ?? ''
        ]
    ];
    
    logEvent("Auth check: User {$_SESSION['email']} is authenticated", 'info');
} else {
    // User is not authenticated
    $response = [
        'authenticated' => false
    ];
    
    logEvent("Auth check: No active session", 'info');
}

echo json_encode($response);
