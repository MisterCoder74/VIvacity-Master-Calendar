# VIvacity-Master-Calendar

A collaborative calendar application with Google OAuth authentication.

## Features

- Google OAuth 2.0 authentication
- Secure session management
- JSON-based user storage
- RESTful API endpoints

## Prerequisites

- PHP 8.3 or higher
- PHP cURL extension enabled
- Web server (Apache, Nginx, or PHP built-in server for development)

## Setup Instructions

### 1. Google Cloud Console Configuration

To enable Google OAuth authentication, you need to configure a Google Cloud project:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API or Google Identity Services:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Google+ API" or "Google Identity"
   - Click "Enable"
4. Create OAuth 2.0 credentials:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client ID"
   - Configure the consent screen if prompted
   - Application type: "Web application"
   - Add authorized redirect URI:
     - For development: `http://localhost:8000/php/auth_callback.php`
     - For production: `https://yourdomain.com/php/auth_callback.php`
5. Copy your Client ID and Client Secret

### 2. Configure OAuth Credentials

1. Copy the example configuration file:
   ```bash
   cp config/google_oauth_config.example.php config/google_oauth_config.php
   ```
2. Open `/config/google_oauth_config.php`
3. Replace the placeholder values:
   ```php
   'client_id' => 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com',
   'client_secret' => 'YOUR_ACTUAL_CLIENT_SECRET',
   ```
4. Update `redirect_uri` if deploying to a domain other than localhost:8000

**SECURITY WARNING:** Never commit your actual credentials to version control. The `google_oauth_config.php` file is included in `.gitignore` to prevent accidental commits.

### 3. File Permissions

Ensure the `/data` directory is writable by the web server:

```bash
chmod 755 /data
chmod 644 /data/users.json
```

### 4. Start the Development Server

For local development, you can use PHP's built-in server:

```bash
php -S localhost:8000
```

Then navigate to `http://localhost:8000` in your browser.

## Project Structure

```
/
├── css/                    # Stylesheets
├── js/                     # JavaScript files
├── php/                    # Backend PHP files
│   ├── config.php         # Main configuration
│   ├── functions.php      # Utility functions
│   ├── auth_callback.php  # OAuth callback handler
│   ├── auth.php           # Session check endpoint
│   └── logout.php         # Logout handler
├── config/                 # Configuration files
│   └── google_oauth_config.php  # OAuth credentials (not in git)
├── data/                   # Data storage
│   └── users.json         # User database (not in git)
└── README.md              # This file
```

## API Endpoints

### Check Authentication Status
```
GET /php/auth.php
```

**Response (authenticated):**
```json
{
  "authenticated": true,
  "user": {
    "user_id": "user_123456",
    "name": "John Doe",
    "email": "john@example.com",
    "profile_picture": "https://..."
  }
}
```

**Response (not authenticated):**
```json
{
  "authenticated": false
}
```

### Logout
```
GET /php/logout.php
```

**Response:**
```json
{
  "success": true
}
```

Redirects to `/index.html` for non-AJAX requests.

## OAuth Flow

1. User clicks "Login with Google" button on the frontend
2. User is redirected to Google's authorization page
3. User grants permission
4. Google redirects back to `/php/auth_callback.php` with authorization code
5. Backend exchanges code for access token
6. Backend fetches user profile information
7. Backend creates/updates user in `users.json`
8. Backend establishes PHP session
9. User is redirected to `/dashboard.html`

## Security Features

- Session cookies are HTTP-only and use SameSite policy
- All user input is sanitized to prevent XSS attacks
- Email addresses are validated before storage
- OAuth Client Secret is never exposed to frontend
- Sessions expire after 24 hours
- Secure password-less authentication via Google

## Development Notes

- Sessions are stored with the name `LC_IDENTIFIER`
- Session lifetime: 24 hours (86400 seconds)
- All timestamps use ISO 8601 format (UTC)
- No file locking is used on JSON files (as per specification)

## Troubleshooting

### "Authorization Error" after Google login
- Verify your redirect URI in Google Cloud Console matches exactly
- Check that your Client ID and Client Secret are correct
- Ensure the Google+ API is enabled

### Session not persisting
- Check that cookies are enabled in your browser
- Verify PHP session settings in `php/config.php`
- Ensure `/tmp` directory is writable (default PHP session storage)

### "Failed to write JSON file"
- Check file permissions on `/data/users.json`
- Ensure the web server has write access to the `/data` directory

## License

[Add your license here]