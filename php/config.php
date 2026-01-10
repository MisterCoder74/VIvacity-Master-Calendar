<?php
// Set session name BEFORE any session_start() call
ini_set('session.name', 'LC_IDENTIFIER');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_lifetime', 86400);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Define base paths
define('ROOT_DIR', dirname(__DIR__));
define('DATA_DIR', ROOT_DIR . '/data');
define('CONFIG_DIR', ROOT_DIR . '/config');

// Load Google OAuth config
$oauth_config = require CONFIG_DIR . '/google_oauth_config.php';

define('OAUTH_CLIENT_ID', $oauth_config['client_id']);
define('OAUTH_CLIENT_SECRET', $oauth_config['client_secret']);
define('OAUTH_REDIRECT_URI', $oauth_config['redirect_uri']);
define('OAUTH_TOKEN_ENDPOINT', $oauth_config['token_endpoint']);
define('OAUTH_USERINFO_ENDPOINT', $oauth_config['userinfo_endpoint']);

// Set timezone
date_default_timezone_set('UTC');
