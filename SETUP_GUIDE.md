# Google OAuth Setup Guide

This guide will walk you through setting up Google OAuth authentication for VIvacity Master Calendar.

## Quick Start

### Step 1: Google Cloud Console Setup (5 minutes)

1. **Create a Google Cloud Project**
   - Visit https://console.cloud.google.com/
   - Click "Select a project" → "New Project"
   - Enter project name: "VIvacity Calendar" (or your preferred name)
   - Click "Create"

2. **Enable Required APIs**
   - In the left sidebar, go to "APIs & Services" → "Library"
   - Search for "Google+ API" or "Google Identity Services"
   - Click on it and press "Enable"

3. **Configure OAuth Consent Screen**
   - Go to "APIs & Services" → "OAuth consent screen"
   - Choose "External" user type (unless you have a Google Workspace)
   - Fill in required fields:
     - App name: VIvacity Master Calendar
     - User support email: your email
     - Developer contact: your email
   - Click "Save and Continue"
   - Skip adding scopes (click "Save and Continue")
   - Add test users if needed (your Google account email)
   - Click "Save and Continue"

4. **Create OAuth 2.0 Credentials**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth 2.0 Client ID"
   - Application type: "Web application"
   - Name: "VIvacity Calendar Web Client"
   - Authorized redirect URIs:
     - Click "Add URI"
     - For local development: `http://localhost:8000/php/auth_callback.php`
     - For production: `https://yourdomain.com/php/auth_callback.php`
   - Click "Create"
   - **IMPORTANT**: Copy your Client ID and Client Secret - you'll need these next!

### Step 2: Configure Your Application (2 minutes)

1. **Update OAuth Configuration**
   - Open the file `/config/google_oauth_config.php` in your project
   - Replace the placeholder values:
   
   ```php
   return [
       'client_id' => 'YOUR_CLIENT_ID_HERE.apps.googleusercontent.com',
       'client_secret' => 'YOUR_CLIENT_SECRET_HERE',
       'redirect_uri' => 'http://localhost:8000/php/auth_callback.php',
       // ... rest of the config stays the same
   ];
   ```

2. **Verify File Permissions**
   ```bash
   chmod 755 data/
   chmod 644 data/users.json
   ```

### Step 3: Test Your Setup (2 minutes)

1. **Start the PHP Development Server**
   ```bash
   php -S localhost:8000
   ```

2. **Test the Authentication Flow**
   - Open your browser to `http://localhost:8000`
   - Click "Login with Google"
   - You should be redirected to Google's login page
   - After authentication, you'll be redirected back to your app
   - Check that your session is active by visiting: `http://localhost:8000/php/auth.php`

## Common Issues and Solutions

### Issue: "redirect_uri_mismatch" Error

**Solution**: The redirect URI in your Google Cloud Console must EXACTLY match the one in your config file.
- No trailing slash
- Exact protocol (http vs https)
- Exact port number
- Exact path

### Issue: "Access blocked: This app's request is invalid"

**Solution**: 
- Make sure you've configured the OAuth consent screen
- Verify the Google+ API is enabled
- Check that your scopes are correct in the config

### Issue: "Invalid client" Error

**Solution**: 
- Double-check your Client ID and Client Secret
- Make sure there are no extra spaces when copying
- Verify the credentials are for a "Web application" type

### Issue: Session Not Persisting

**Solution**:
- Clear your browser cookies
- Make sure cookies are enabled
- Check that your PHP session directory is writable: `ls -la /tmp | grep sess`

## Production Deployment Checklist

Before deploying to production:

- [ ] Update `redirect_uri` in `/config/google_oauth_config.php` to use your production domain with HTTPS
- [ ] Add production redirect URI to Google Cloud Console
- [ ] Update Google OAuth consent screen with production domain
- [ ] Set up HTTPS/SSL certificate
- [ ] Secure file permissions on server
- [ ] Configure proper PHP session settings for production
- [ ] Set up error logging
- [ ] Add production domain to CORS settings if needed
- [ ] Test OAuth flow on production domain
- [ ] Add `/config/google_oauth_config.php` to `.gitignore` (already done)
- [ ] Consider using environment variables for sensitive config

## Security Best Practices

1. **Never Commit Credentials**
   - The `.gitignore` file already excludes `google_oauth_config.php`
   - Always use environment variables in production
   - Never share your Client Secret publicly

2. **Use HTTPS in Production**
   - OAuth requires HTTPS for production domains
   - Let's Encrypt provides free SSL certificates

3. **Validate All User Input**
   - All functions in `functions.php` sanitize user input
   - Email validation is performed before storing users

4. **Session Security**
   - Sessions are configured with httpOnly cookies
   - SameSite policy is set to 'Lax'
   - Sessions expire after 24 hours

## Environment Variables (Recommended for Production)

Instead of hardcoding credentials, use environment variables:

1. Create a `.env` file (also in `.gitignore`):
   ```
   GOOGLE_CLIENT_ID=your_client_id_here
   GOOGLE_CLIENT_SECRET=your_client_secret_here
   REDIRECT_URI=https://yourdomain.com/php/auth_callback.php
   ```

2. Load environment variables in `config.php`:
   ```php
   // Load .env file
   if (file_exists(ROOT_DIR . '/.env')) {
       $lines = file(ROOT_DIR . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
       foreach ($lines as $line) {
           if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
               putenv($line);
           }
       }
   }
   ```

3. Update `google_oauth_config.php`:
   ```php
   return [
       'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID',
       'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET',
       'redirect_uri' => getenv('REDIRECT_URI') ?: 'http://localhost:8000/php/auth_callback.php',
       // ...
   ];
   ```

## Testing the API Endpoints

### Check Authentication Status
```bash
curl http://localhost:8000/php/auth.php
```

Expected response (not logged in):
```json
{"authenticated":false}
```

Expected response (logged in):
```json
{
  "authenticated": true,
  "user": {
    "user_id": "user_12345...",
    "name": "John Doe",
    "email": "john@example.com",
    "profile_picture": "https://..."
  }
}
```

### Logout
```bash
curl http://localhost:8000/php/logout.php
```

Expected response:
```json
{"success":true}
```

## Support and Troubleshooting

If you encounter issues not covered in this guide:

1. Check the PHP error log: `tail -f /var/log/php_errors.log`
2. Check browser console for JavaScript errors
3. Verify all file paths are correct
4. Ensure all files have proper permissions
5. Review the OAuth flow in `auth_callback.php` comments

## Additional Resources

- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)
- [PHP Session Documentation](https://www.php.net/manual/en/book.session.php)
