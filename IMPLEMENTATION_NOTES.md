# Phase 1, Task 1: Implementation Notes

## What Was Built

This implementation provides a complete Google OAuth 2.0 authentication backend for the VIvacity Master Calendar application.

## Files Created

### Configuration Files
- **`/config/google_oauth_config.php`** - OAuth credentials (gitignored, for local use)
- **`/config/google_oauth_config.example.php`** - Example OAuth config (committed to git)

### Data Files
- **`/data/users.json`** - User database (gitignored, created empty)
- **`/data/users.example.json`** - Example user data structure (committed to git)

### PHP Backend Files
- **`/php/config.php`** - Main configuration file
  - Sets up paths and constants
  - Configures secure session settings
  - Loads OAuth configuration
  - Includes utility functions

- **`/php/functions.php`** - Utility functions library
  - `readJsonFile()` - Read and decode JSON files
  - `writeJsonFile()` - Write data to JSON files
  - `generateUniqueId()` - Generate unique user IDs
  - `sanitizeInput()` - XSS prevention
  - `validateEmail()` - Email validation
  - `getCurrentUser()` - Get current session user
  - `logEvent()` - Event logging
  - `findUserByGoogleId()` - Find user by Google ID
  - `findUserByEmail()` - Find user by email
  - `createUser()` - Create new user
  - `updateLastLogin()` - Update last login timestamp
  - `sendJsonResponse()` - Send JSON HTTP response

- **`/php/auth_callback.php`** - OAuth callback handler
  - Receives authorization code from Google
  - Exchanges code for access token
  - Fetches user profile information
  - Creates new users or updates existing users
  - Establishes PHP session
  - Redirects to dashboard
  - Comprehensive error handling with user-friendly messages

- **`/php/auth.php`** - Session status endpoint
  - Returns JSON with authentication status
  - Provides current user information if logged in
  - Used by frontend to check login status

- **`/php/logout.php`** - Logout handler
  - Destroys PHP session
  - Clears session cookies
  - Returns JSON response for AJAX requests
  - Redirects to index for regular requests

### Documentation
- **`README.md`** - Updated with comprehensive setup instructions
- **`SETUP_GUIDE.md`** - Detailed step-by-step Google OAuth setup guide
- **`IMPLEMENTATION_NOTES.md`** - This file

### Other Files
- **`.gitignore`** - Protects sensitive files from being committed

## Security Features Implemented

1. **Session Security**
   - HTTPOnly cookies (prevents XSS cookie theft)
   - SameSite policy set to 'Lax'
   - 24-hour session lifetime
   - Session regeneration after login
   - Custom session name: `LC_IDENTIFIER`

2. **Input Validation & Sanitization**
   - All user input sanitized with `htmlspecialchars()`
   - Email validation with `filter_var()`
   - URL encoding for OAuth parameters

3. **OAuth Security**
   - Server-to-server token exchange
   - Client Secret never exposed to frontend
   - Access tokens not stored (only user metadata)
   - Secure credential configuration in gitignored file

4. **Error Handling**
   - User-friendly error messages
   - No sensitive information in error responses
   - Comprehensive error logging
   - Graceful handling of network failures

## OAuth Flow Implementation

```
┌─────────┐                ┌────────┐                ┌──────────────┐
│ Browser │                │  App   │                │    Google    │
└────┬────┘                └───┬────┘                └──────┬───────┘
     │                         │                            │
     │  1. Click Login         │                            │
     ├────────────────────────>│                            │
     │                         │                            │
     │  2. Redirect to Google  │                            │
     ├─────────────────────────┼───────────────────────────>│
     │                         │                            │
     │  3. User authenticates  │                            │
     │<─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─┼─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─>│
     │                         │                            │
     │  4. Redirect with code  │                            │
     │<────────────────────────┼────────────────────────────┤
     │                         │                            │
     │  5. auth_callback.php   │                            │
     ├────────────────────────>│                            │
     │                         │                            │
     │                         │  6. Exchange code for token│
     │                         ├───────────────────────────>│
     │                         │                            │
     │                         │  7. Return access token    │
     │                         │<───────────────────────────┤
     │                         │                            │
     │                         │  8. Get user info          │
     │                         ├───────────────────────────>│
     │                         │                            │
     │                         │  9. Return user profile    │
     │                         │<───────────────────────────┤
     │                         │                            │
     │                         │ 10. Create/update user     │
     │                         │     in users.json          │
     │                         │                            │
     │                         │ 11. Start session          │
     │                         │                            │
     │  12. Redirect to        │                            │
     │      dashboard          │                            │
     │<────────────────────────┤                            │
     │                         │                            │
```

## User Data Schema

Each user in `users.json` contains:
- `id` - Unique internal user ID (generated with `uniqid()`)
- `google_id` - Google's unique identifier for the user
- `name` - User's full name from Google profile
- `email` - User's email address
- `profile_picture` - URL to user's Google profile photo
- `timezone` - User's timezone (default: UTC)
- `created_at` - ISO 8601 timestamp of account creation
- `last_login` - ISO 8601 timestamp of last login
- `preferences` - Object containing user preferences
  - `notifications_enabled` - Boolean
  - `default_view` - String (e.g., "month", "week", "day")

## API Endpoints

### GET /php/auth.php
Check if user is authenticated.

**Response (authenticated):**
```json
{
  "authenticated": true,
  "user": {
    "user_id": "user_xyz",
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

### GET /php/logout.php
Logout the current user.

**Response (AJAX):**
```json
{
  "success": true
}
```

**Response (regular):** Redirects to `/index.html`

### GET /php/auth_callback.php?code=...
OAuth callback endpoint (used by Google, not called directly by frontend).

## Testing Checklist

To test the implementation:

1. ✅ Configure Google OAuth credentials
2. ✅ Start PHP server: `php -S localhost:8000`
3. ✅ Visit auth check endpoint: `curl http://localhost:8000/php/auth.php`
   - Should return `{"authenticated":false}`
4. ✅ Implement frontend login button (Phase 1, Task 2)
5. ✅ Click login button, authenticate with Google
6. ✅ Verify redirect to dashboard
7. ✅ Check auth endpoint again: `curl http://localhost:8000/php/auth.php`
   - Should return authenticated user data
8. ✅ Verify user created in `/data/users.json`
9. ✅ Test logout: `curl http://localhost:8000/php/logout.php`
10. ✅ Login again with same Google account
    - Verify `last_login` updated in `users.json`

## Next Steps

Phase 1, Task 2 will implement the frontend:
- Login page with Google Sign-In button
- Dashboard page
- Session checking on page load
- Logout functionality

## Notes for Developers

1. **No File Locking**: As per specification, JSON files are read/written without locking. This is acceptable for a small-scale application but should be replaced with a proper database for production.

2. **Session Storage**: PHP sessions are stored in the default location (`/tmp` on most systems). For production, consider using Redis or database-backed sessions.

3. **Error Logging**: Currently logs to PHP's default error log. Configure `error_log` in `php.ini` or use a dedicated logging service for production.

4. **HTTPS Required**: OAuth requires HTTPS for production deployments. Use Let's Encrypt for free SSL certificates.

5. **Environment Variables**: The setup guide includes instructions for using environment variables instead of hardcoded credentials for production deployments.

6. **CORS**: If frontend and backend are on different domains, you'll need to configure CORS headers.

## Files to Keep Secret

These files are in `.gitignore` and should NEVER be committed:
- `/config/google_oauth_config.php` (contains OAuth credentials)
- `/data/users.json` (contains user data)
- `.env` (if using environment variables)

## Troubleshooting

See `SETUP_GUIDE.md` for detailed troubleshooting steps.

Common issues:
- **redirect_uri_mismatch**: URI in Google Console doesn't match config
- **Invalid client**: Wrong Client ID or Client Secret
- **Session not persisting**: Check cookie settings and browser
- **JSON write errors**: Check file permissions on `/data` directory
