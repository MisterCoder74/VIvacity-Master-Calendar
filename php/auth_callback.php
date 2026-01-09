<?php
/**
 * OAuth Callback Handler
 * Handles the redirect from Google OAuth and exchanges authorization code for user info
 * 
 * OAuth Flow:
 * 1. User authenticates with Google
 * 2. Google redirects here with authorization code
 * 3. Exchange code for access token
 * 4. Use token to fetch user profile info
 * 5. Create/update user in database
 * 6. Establish session
 * 7. Redirect to dashboard
 */

require_once __DIR__ . '/config.php';

// Start session
session_start();

/**
 * Display error message and log
 */
function handleError($message, $logMessage = null) {
    logEvent($logMessage ?? $message, 'error');
    
    // In production, you might want to redirect to an error page instead
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Authentication Error</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; }
        .error { background: #ffebee; border: 1px solid #ef5350; border-radius: 4px; padding: 20px; color: #c62828; }
        h2 { margin-top: 0; color: #c62828; }
        a { color: #1976d2; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class='error'>
        <h2>Authentication Error</h2>
        <p>" . htmlspecialchars($message) . "</p>
        <p><a href="../">Return to login page</a></p>
    </div>
</body>
</html>";
    exit;
}

// Check if authorization code is present
if (!isset($_GET['code'])) {
    // Check if there's an error from Google
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
        handleError(
            'Google authentication was cancelled or failed. Please try again.',
            "OAuth error: $error"
        );
    }
    handleError('No authorization code received from Google.');
}

$authCode = $_GET['code'];

// Exchange authorization code for access token
$tokenData = [
    'code' => $authCode,
    'client_id' => OAUTH_CLIENT_ID,
    'client_secret' => OAUTH_CLIENT_SECRET,
    'redirect_uri' => OAUTH_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init(OAUTH_TOKEN_ENDPOINT);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    handleError(
        'Failed to connect to Google authentication service. Please try again later.',
        "cURL error exchanging code for token: $error"
    );
}

curl_close($ch);

if ($httpCode !== 200) {
    handleError(
        'Failed to authenticate with Google. Please try again.',
        "Token endpoint returned HTTP $httpCode: $tokenResponse"
    );
}

$tokenJson = json_decode($tokenResponse, true);
if (!isset($tokenJson['access_token'])) {
    handleError(
        'Invalid response from Google authentication service.',
        "No access token in response: $tokenResponse"
    );
}

$accessToken = $tokenJson['access_token'];

// Fetch user information using access token
$ch = curl_init(OAUTH_USERINFO_ENDPOINT . '?access_token=' . urlencode($accessToken));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$userInfoResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    handleError(
        'Failed to retrieve user information from Google. Please try again.',
        "cURL error fetching user info: $error"
    );
}

curl_close($ch);

if ($httpCode !== 200) {
    handleError(
        'Failed to retrieve user information from Google.',
        "Userinfo endpoint returned HTTP $httpCode: $userInfoResponse"
    );
}

$userInfo = json_decode($userInfoResponse, true);

// Validate required user information
if (!isset($userInfo['id']) || !isset($userInfo['email'])) {
    handleError(
        'Incomplete user information received from Google.',
        "Missing required fields in user info: $userInfoResponse"
    );
}

// Validate email format
if (!validateEmail($userInfo['email'])) {
    handleError(
        'Invalid email address received from Google.',
        "Invalid email format: {$userInfo['email']}"
    );
}

// Extract user data
$googleId = $userInfo['id'];
$email = $userInfo['email'];
$name = $userInfo['name'] ?? $email;
$profilePicture = $userInfo['picture'] ?? '';

// Check if user exists
$existingUser = findUserByGoogleId($googleId);

if ($existingUser) {
    // Existing user - update last login
    updateLastLogin($googleId);
    $user = $existingUser;
    logEvent("User logged in: $email", 'info');
} else {
    // New user - create account
    $userData = [
        'google_id' => $googleId,
        'email' => $email,
        'name' => $name,
        'profile_picture' => $profilePicture
    ];
    
    $user = createUser($userData);
    
    if (!$user) {
        handleError(
            'Failed to create user account. Please try again.',
            "Failed to create user for email: $email"
        );
    }
    
    logEvent("New user registered: $email", 'info');
}

// Store user information in session
$_SESSION['user_id'] = $user['id'];
$_SESSION['google_id'] = $user['google_id'];
$_SESSION['email'] = $user['email'];
$_SESSION['name'] = $user['name'];
$_SESSION['profile_picture'] = $user['profile_picture'];

// Regenerate session ID for security
session_regenerate_id(true);

logEvent("Session established for user: {$user['email']}", 'info');

// Redirect to dashboard
header('Location: ../dashboard.html');
exit;
