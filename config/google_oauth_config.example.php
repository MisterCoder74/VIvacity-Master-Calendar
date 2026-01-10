<?php
/**
 * Google OAuth Configuration - EXAMPLE FILE
 *
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to google_oauth_config.php in the same directory
 * 2. Go to Google Cloud Console (https://console.cloud.google.com/)
 * 3. Create a new project or select an existing one
 * 4. Enable the Google+ API or Google Identity Services
 * 5. Go to "Credentials" and create an OAuth 2.0 Client ID
 * 6. Set authorized redirect URIs to match your redirect_uri below
 * 7. Copy your Client ID and Client Secret and update the values in google_oauth_config.php
 *
 * SECURITY WARNING:
 * - Never commit google_oauth_config.php with real credentials to version control
 * - This file is in .gitignore
 * - Keep your Client Secret secure and never expose it to frontend code
 * - Use environment variables in production instead of hardcoded values
 *
 * DEPLOYMENT NOTES:
 * - For localhost development: use http://localhost:8000/php/auth_callback.php
 * - For production: update redirect_uri to use HTTPS and your actual domain
 */

return [
    // OAuth Client ID from Google Cloud Console
    'client_id' => 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',

    // OAuth Client Secret from Google Cloud Console (KEEP SECURE!)
    'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',

    // Redirect URI - must match exactly what's configured in Google Cloud Console
    // Update this when deploying to production
    'redirect_uri' => 'https://testsite.vivacitydesign.net/CTO-TESTS/VIvacity-Master-Calendar-main/php/auth_callback.php',

    // OAuth scopes - what information we're requesting from Google
    'auth_scope' => 'openid email profile',

    // Google OAuth API endpoints
    'google_api_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_endpoint' => 'https://oauth2.googleapis.com/token',
    'userinfo_endpoint' => 'https://www.googleapis.com/oauth2/v2/userinfo'
];
