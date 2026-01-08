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
    'scope' => OAUTH_AUTH_SCOPE,
    'google_api_endpoint' => OAUTH_GOOGLE_API_ENDPOINT
];

logEvent("OAuth config requested", 'info');

echo json_encode($response);
