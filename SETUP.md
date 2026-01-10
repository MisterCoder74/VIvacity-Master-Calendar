# VIvacity Master Calendar - Minimal Setup Guide

## Overview
This is a minimal implementation of the VIvacity Master Calendar with only Google OAuth authentication and a welcome dashboard. This provides a clean foundation for incremental feature development.

## Files Structure

```
VIvacity-Master-Calendar-main/
├── index.html                          # Login page with Google Sign-In
├── dashboard.php                       # Welcome page ("Benvenuti")
├── config/
│   └── google_oauth_config.example.php  # OAuth configuration example
├── php/
│   ├── config.php                      # Session & paths configuration
│   ├── functions.php                   # Utility functions
│   ├── auth_callback.php               # OAuth callback handler
│   ├── get_oauth_config.php            # OAuth config endpoint
│   └── logout.php                     # Logout handler
├── data/
│   ├── users.json                      # User data storage (auto-created)
│   └── users.example.json              # Example user structure
├── css/
│   └── style.css                      # Minimal styling
├── js/
│   └── auth.js                        # Authentication module
├── README.md                          # Project documentation
├── .gitignore                         # Git ignore rules
└── .htaccess                          # Apache configuration
```

## Setup Instructions

### 1. Configure Google OAuth

1. Copy the example config file:
   ```bash
   cp config/google_oauth_config.example.php config/google_oauth_config.php
   ```

2. Go to [Google Cloud Console](https://console.cloud.google.com/)

3. Create or select a project and enable the Google Identity Services API

4. Create OAuth 2.0 credentials:
   - Go to "Credentials" > "Create Credentials" > "OAuth 2.0 Client ID"
   - Application type: Web application
   - Authorized redirect URI:
     ```
     https://testsite.vivacitydesign.net/CTO-TESTS/VIvacity-Master-Calendar-main/php/auth_callback.php
     ```

5. Copy the Client ID and Client Secret into `config/google_oauth_config.php`:
   ```php
   return [
       'client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com',
       'client_secret' => 'YOUR_CLIENT_SECRET',
       'redirect_uri' => 'https://testsite.vivacitydesign.net/CTO-TESTS/VIvacity-Master-Calendar-main/php/auth_callback.php',
       'auth_scope' => 'openid email profile',
       'google_api_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
       'token_endpoint' => 'https://oauth2.googleapis.com/token',
       'userinfo_endpoint' => 'https://www.googleapis.com/oauth2/v2/userinfo'
   ];
   ```

### 2. Deploy Files

Upload all files to your web server. Ensure the following directories are writable by PHP:
- `data/` (for users.json)

### 3. Test the Application

1. Navigate to `index.html`
2. Click "Sign in with Google"
3. Authenticate with your Google account
4. Verify you are redirected to `dashboard.php`
5. Verify you see "Benvenuti" message with your username
6. Click "Logout" and verify you return to login page

## Features Included

- ✅ Google OAuth 2.0 authentication
- ✅ User data persistence (JSON file)
- ✅ Session management
- ✅ Secure logout
- ✅ Minimal, clean UI

## Features NOT Included (for future development)

- ❌ Calendar views
- ❌ Task management
- ❌ Event management
- ❌ Time blocking
- ❌ Sync functionality

## Security Notes

- Session name is set to `LC_IDENTIFIER` for isolation
- Session cookies are HTTP-only and SameSite Lax
- Client Secret is never exposed to frontend
- User data is stored in JSON files with proper permissions
- Session ID is regenerated after login for security

## Next Steps

1. Test authentication flow thoroughly
2. Add calendar UI (month view)
3. Implement task CRUD operations
4. Implement event CRUD operations
5. Add synchronization features

## Troubleshooting

### "No authorization code received" error
- Check that redirect URI matches exactly in Google Cloud Console
- Verify the URL includes the full path to `auth_callback.php`

### User data not saving
- Check that `data/` directory is writable
- Verify PHP has write permissions

### Session issues
- Check that `LC_IDENTIFIER` session name is consistent
- Verify cookie settings in browser

## Support

For issues or questions, refer to the main README.md or project documentation.
