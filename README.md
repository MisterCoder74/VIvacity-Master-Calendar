# VIvacity Master Calendar

A minimal calendar application with Google OAuth authentication and a welcome dashboard.

## Overview

This is a clean slate implementation providing only:
- Google OAuth 2.0 authentication
- User data persistence (JSON)
- Welcome dashboard with logout functionality

This minimal foundation is ready for incremental feature development.

## Features

- ✅ Google OAuth 2.0 authentication
- ✅ Secure session management
- ✅ JSON-based user storage
- ✅ Clean, minimal UI
- ✅ User profile picture support

## Prerequisites

- PHP 8.0 or higher
- PHP cURL extension enabled
- Web server (Apache, Nginx, or PHP built-in server for development)

## Quick Start

### 1. Google Cloud Console Configuration

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable Google Identity Services API:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Google Identity"
   - Click "Enable"
4. Create OAuth 2.0 credentials:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client ID"
   - Application type: "Web application"
   - Authorized redirect URI:
     ```
     https://testsite.vivacitydesign.net/CTO-TESTS/VIvacity-Master-Calendar-main/php/auth_callback.php
     ```
5. Copy your Client ID and Client Secret

### 2. Configure OAuth Credentials

1. Copy the example configuration file:
   ```bash
   cp config/google_oauth_config.example.php config/google_oauth_config.php
   ```

2. Open `config/google_oauth_config.php` and replace placeholder values:
   ```php
   'client_id' => 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com',
   'client_secret' => 'YOUR_ACTUAL_CLIENT_SECRET',
   ```

3. Ensure the `redirect_uri` matches what you configured in Google Cloud Console.

**SECURITY WARNING:** Never commit your actual credentials to version control. The `google_oauth_config.php` file is included in `.gitignore` to prevent accidental commits.

### 3. File Permissions

Ensure the `/data` directory is writable by the web server:

```bash
chmod 755 data/
chmod 644 data/users.json
```

### 4. Deploy and Test

1. Deploy all files to your web server
2. Navigate to `index.html`
3. Click "Sign in with Google"
4. Authenticate with your Google account
5. Verify you see the welcome dashboard with "Benvenuti" message
6. Test logout functionality

## Project Structure

```
VIvacity-Master-Calendar-main/
├── index.html                          # Login page
├── dashboard.php                       # Welcome dashboard
├── config/
│   └── google_oauth_config.example.php  # OAuth config template
├── php/
│   ├── config.php                      # Session & paths config
│   ├── functions.php                   # Utility functions
│   ├── auth_callback.php               # OAuth callback handler
│   ├── get_oauth_config.php            # OAuth config endpoint
│   └── logout.php                     # Logout handler
├── data/
│   ├── users.json                      # User data storage (auto-created)
│   └── users.example.json              # Example structure
├── css/
│   └── style.css                      # Minimal styling
├── js/
│   └── auth.js                        # Authentication module
├── SETUP.md                           # Detailed setup guide
├── README.md                          # This file
├── .gitignore                         # Git ignore rules
└── .htaccess                          # Apache configuration
```

## OAuth Flow

1. User clicks "Sign in with Google" on login page
2. Google Sign-In JavaScript library initializes
3. User is redirected to Google's authorization page
4. User grants permission to access their profile
5. Google redirects back to `php/auth_callback.php` with authorization code
6. Backend exchanges authorization code for access token
7. Backend fetches user profile information using access token
8. Backend creates or updates user record in `data/users.json`
9. Backend establishes PHP session with user data
10. User is redirected to `dashboard.php`
11. Dashboard displays "Benvenuti" message with user's name
12. Clicking "Logout" clears session and redirects to login page

## Security Features

- Session cookies are HTTP-only and use SameSite Lax policy
- Session name set to `LC_IDENTIFIER` for isolation
- All user input is sanitized to prevent XSS attacks
- Email addresses are validated before storage
- OAuth Client Secret is never exposed to frontend
- Sessions expire after 24 hours
- Session ID is regenerated after login for security
- Secure password-less authentication via Google OAuth

## API Endpoints

### OAuth Configuration
```
GET /php/get_oauth_config.php
```
Returns non-sensitive OAuth configuration to frontend.

### OAuth Callback
```
GET /php/auth_callback.php?code=AUTHORIZATION_CODE
```
Handles Google OAuth callback, creates/updates user, establishes session.

### Logout
```
GET /php/logout.php
```
Destroys session and redirects to index.html.

## Data Storage

User data is stored in `data/users.json` with the following structure:

```json
{
  "users": [
    {
      "id": "user_abc123...",
      "google_id": "google_id_123",
      "email": "user@example.com",
      "name": "John Doe",
      "picture": "https://...",
      "created_at": "2024-01-01T00:00:00+00:00",
      "last_login": "2024-01-01T12:00:00+00:00"
    }
  ]
}
```

## Development Notes

- Sessions are stored with name `LC_IDENTIFIER`
- Session lifetime: 24 hours (86400 seconds)
- All timestamps use ISO 8601 format (UTC)
- No file locking is used on JSON files
- User data is automatically created on first login

## Troubleshooting

### "No authorization code received from Google"
- Verify redirect URI in Google Cloud Console matches exactly
- Ensure you're using HTTPS in production environments
- Check that the OAuth consent screen is configured

### User data not saving
- Check that `data/` directory is writable by web server
- Verify PHP has write permissions
- Check server error logs for permission issues

### Session not persisting between pages
- Check that cookies are enabled in your browser
- Verify PHP session settings in `php/config.php`
- Ensure session name `LC_IDENTIFIER` is consistent
- Check browser's incognito/private browsing mode

### Google Sign-In button not appearing
- Verify Google Sign-In library is loading (check browser console)
- Ensure `php/get_oauth_config.php` is accessible
- Check that Client ID is configured correctly
- Verify network connectivity to Google's servers

### Redirect loop between login and dashboard
- Check that `session_start()` is called before any output
- Verify `$_SESSION['user_id']` is being set correctly
- Ensure session cookie parameters are compatible with your domain
- Clear browser cookies and try again

## Future Development

This minimal foundation is ready for incremental feature additions:

1. Calendar UI (month view)
2. Task management (CRUD operations)
3. Event management (CRUD operations)
4. Time blocking functionality
5. Synchronization with external services

See `SETUP.md` for more detailed setup instructions.

## License

[Add your license here]
