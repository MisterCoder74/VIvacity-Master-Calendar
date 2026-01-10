<?php
/**
 * OAuth Configuration Endpoint
 * Returns OAuth configuration values to frontend JavaScript
 * Security: Only returns non-sensitive values (no client secret)
 */

require_once __DIR__ . '/config.php';

// Set headers for JSON response and prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Return OAuth configuration (without client secret)
$response = [
    'client_id' => OAUTH_CLIENT_ID,
    'redirect_uri' => OAUTH_REDIRECT_URI,
    'scope' => 'openid email profile',
    'google_api_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth'
];

echo json_encode($response);
