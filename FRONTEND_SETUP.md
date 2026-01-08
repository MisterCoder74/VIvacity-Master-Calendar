# Frontend Login & Session Management

## Overview

This document describes the frontend implementation for Google OAuth login and session management in the VIvacity Master Calendar application.

## Files Created/Modified

### Frontend Files
- `/index.html` - Login page with Google Sign-In button
- `/dashboard.html` - Dashboard page with user profile and logout
- `/css/style.css` - Custom styling for login and dashboard pages
- `/js/auth.js` - Authentication logic and session management
- `/terms.html` - Terms of service page (placeholder)
- `/privacy.html` - Privacy policy page (placeholder)
- `/help.html` - Help page (placeholder)
- `/.htaccess` - Apache configuration for security and routing

### Backend Files
- `/php/get_oauth_config.php` - Returns OAuth config to frontend (without secret)

## Authentication Flow

### 1. User Login Flow
1. User loads `/index.html`
2. Page calls `Auth.initializeAuth()` on load
3. `Auth.checkSession()` verifies if user is already authenticated
4. If not authenticated, Google Sign-In library initializes
5. User clicks "Sign in with Google" button
6. Google Sign-In library calls `Auth.onSignIn(response)` with JWT credential
7. Frontend extracts user info from JWT and stores temporarily
8. Frontend calls `/php/get_oauth_config.php` to get OAuth config
9. Frontend builds OAuth authorization URL with CSRF state
10. Browser redirects to Google OAuth authorization page
11. User grants permission (if not already granted)
12. Google redirects to `/php/auth_callback.php` with authorization code
13. Backend exchanges code for access token
14. Backend fetches user info and creates/updates user in JSON
15. Backend establishes PHP session
16. Backend redirects to `/dashboard.html`

### 2. Session Check Flow
1. Dashboard loads and calls `Auth.checkSession()`
2. `Auth.checkSession()` calls `/php/auth.php` endpoint
3. Backend returns JSON with authentication status and user data
4. If authenticated:
   - User data stored in `localStorage` with key `vivacity_user`
   - Dashboard displays user information
5. If not authenticated:
   - User redirected to `/index.html`
   - `localStorage` cleared

### 3. Logout Flow
1. User clicks logout button on dashboard
2. Frontend calls `Auth.logout()`
3. `Auth.logout()` calls `/php/logout.php` endpoint
4. Backend destroys PHP session
5. Frontend clears `localStorage` and `sessionStorage`
6. Frontend disables Google Sign-In auto-sign-in
7. Frontend redirects to `/index.html`

## JavaScript API

### Auth Module (`/js/auth.js`)

#### Methods

**`async checkSession()`**
- Checks if user is authenticated via `/php/auth.php` endpoint
- Returns user object if authenticated, null otherwise
- Stores user data in localStorage for quick access

**`parseJwt(token)`**
- Parses JWT token to extract user information
- Returns decoded payload object
- Throws error if token is invalid

**`async onSignIn(response)`**
- Callback for Google Sign-In button
- Extracts user info from JWT credential
- Redirects to Google OAuth authorization page
- Stores CSRF state in sessionStorage

**`async logout()`**
- Destroys session on backend
- Clears localStorage and sessionStorage
- Disables Google Sign-In auto-sign-in
- Redirects to login page

**`async initializeAuth()`**
- Initializes authentication on page load
- Checks if user is authenticated
- Redirects to dashboard if authenticated
- Initializes Google Sign-In if not authenticated

**`initializeGoogleSignIn()`**
- Initializes Google Sign-In library
- Fetches OAuth config from backend
- Renders Google Sign-In button

**`showToast(message, type)`**
- Displays toast notification
- Types: 'success' or 'error'
- Auto-dismisses after 5 seconds

**`getCurrentUser()`**
- Gets current user from localStorage
- Returns user object or null

**`toggleUserDropdown()`**
- Toggles user dropdown menu visibility

**`closeUserDropdown()`**
- Closes user dropdown menu

## CSS Features

### Responsive Design
- Mobile-first approach
- Breakpoints: < 768px (mobile), 768-1024px (tablet), > 1024px (desktop)
- Font sizes scale down on mobile
- Navbar stacks vertically on mobile

### Visual Design
- Gradient background on login page
- Box shadows for depth
- Smooth transitions (0.3s) for hover effects
- Circular profile pictures with border
- Loading spinner for async operations

### Accessibility
- High contrast colors
- Clear visual hierarchy
- Loading indicators for async operations
- No-JS fallback message

## Security Features

1. **CSRF Protection**
   - Random state parameter generated for OAuth flow
   - State stored in sessionStorage
   - State validated on callback (in backend)

2. **Session Security**
   - HTTPOnly cookies
   - SameSite policy set to 'Lax'
   - Session regeneration after login
   - 24-hour session lifetime

3. **XSS Protection**
   - Content-Type headers set
   - X-Content-Type-Options: nosniff
   - X-Frame-Options: SAMEORIGIN
   - X-XSS-Protection enabled

4. **No Sensitive Data in Frontend**
   - Client secret never exposed to frontend
   - OAuth config endpoint returns only safe values
   - No tokens stored in localStorage (only user profile data)

## Browser Compatibility

- Modern browsers with ES6+ support
- Google Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Dependencies

### External Libraries
- Bootstrap 5.3.2 (CSS and JS)
- Google Identity Services (Google Sign-In)

### Internal Dependencies
- `/php/auth.php` - Session check endpoint
- `/php/logout.php` - Logout endpoint
- `/php/get_oauth_config.php` - OAuth config endpoint
- `/php/auth_callback.php` - OAuth callback handler

## Setup Instructions

### 1. Google Cloud Console Setup
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google Identity Services API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
5. Configure consent screen (if prompted)
6. Add authorized redirect URIs:
   - Development: `http://localhost:8000/php/auth_callback.php`
   - Production: `https://yourdomain.com/php/auth_callback.php`
7. Copy Client ID and Client Secret

### 2. Update Configuration
Edit `/config/google_oauth_config.php`:
```php
return [
    'client_id' => 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
    'redirect_uri' => 'http://localhost:8000/php/auth_callback.php',
    // ... other config
];
```

### 3. Start Development Server
```bash
cd /home/engine/project
php -S localhost:8000
```

### 4. Test the Application
1. Open `http://localhost:8000` in browser
2. Click "Sign in with Google"
3. Complete Google authorization
4. Verify you're redirected to dashboard
5. Check user profile is displayed
6. Click logout to verify logout flow

## Testing Checklist

- [ ] Login page loads without errors
- [ ] Google Sign-In button visible and styled correctly
- [ ] Click "Sign in with Google" redirects to Google
- [ ] After authorization, redirected to `/dashboard.html`
- [ ] User name and profile picture displayed
- [ ] `localStorage` contains `vivacity_user` key
- [ ] Refreshing dashboard maintains authentication
- [ ] Logout button clears session and redirects to login
- [ ] Accessing `/dashboard.html` without login redirects to login
- [ ] Mobile responsive (test in DevTools mobile view)
- [ ] No console errors
- [ ] Toast notifications work correctly
- [ ] Session persists for 24 hours

## Troubleshooting

### Google Sign-In button not showing
- Check browser console for errors
- Verify Google Sign-In library loaded from CDN
- Check network tab for failed requests to `/php/get_oauth_config.php`

### Session check failing
- Check `/php/auth.php` endpoint is accessible
- Verify PHP sessions are working
- Check browser cookies are enabled

### Redirect loops
- Clear browser cookies and localStorage
- Check `/php/auth_callback.php` is completing successfully
- Verify OAuth configuration is correct

### OAuth error
- Verify redirect URI matches Google Cloud Console exactly
- Check Client ID and Client Secret are correct
- Verify Google Identity Services API is enabled

## Future Enhancements

- Remember me functionality
- Multi-factor authentication
- Social login providers (Facebook, Apple)
- Profile editing
- Password reset flow
- Email verification
- Account deletion
