<?php
/**
 * Main Configuration File
 * Sets up base paths, error handling, and includes OAuth configuration
 */

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.name', 'LC_IDENTIFIER');

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);

// Define base paths
define('ROOT_DIR', dirname(__DIR__));
define('PHP_DIR', ROOT_DIR . '/php');
define('CONFIG_DIR', ROOT_DIR . '/config');
define('DATA_DIR', ROOT_DIR . '/data');

// Define JSON file paths
define('USERS_JSON_PATH', DATA_DIR . '/users.json');

// Load Google OAuth configuration
$oauth_config = require CONFIG_DIR . '/google_oauth_config.php';

// Make OAuth config globally accessible
define('OAUTH_CLIENT_ID', $oauth_config['client_id']);
define('OAUTH_CLIENT_SECRET', $oauth_config['client_secret']);
define('OAUTH_REDIRECT_URI', $oauth_config['redirect_uri']);
define('OAUTH_AUTH_SCOPE', $oauth_config['auth_scope']);
define('OAUTH_GOOGLE_API_ENDPOINT', $oauth_config['google_api_endpoint']);
define('OAUTH_TOKEN_ENDPOINT', $oauth_config['token_endpoint']);
define('OAUTH_USERINFO_ENDPOINT', $oauth_config['userinfo_endpoint']);

// Set default timezone
date_default_timezone_set('UTC');

// Include utility functions
require_once PHP_DIR . '/functions.php';
