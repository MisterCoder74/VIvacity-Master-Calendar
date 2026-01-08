# Testing Guide - Frontend Login & Session Management

## Prerequisites

Before testing, you need to:

1. **Set up Google OAuth credentials**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a project or select existing one
   - Enable Google Identity Services API
   - Create OAuth 2.0 Client ID credentials
   - Add authorized redirect URI: `http://localhost:8000/php/auth_callback.php`
   - Copy Client ID and Client Secret

2. **Update configuration**
   - Edit `/config/google_oauth_config.php`
   - Replace `YOUR_GOOGLE_CLIENT_ID` with your actual Client ID
   - Replace `YOUR_GOOGLE_CLIENT_SECRET` with your actual Client Secret

3. **Start PHP development server**
   ```bash
   cd /home/engine/project
   php -S localhost:8000
   ```

## Test Scenarios

### Test 1: Login Page Display
**Steps:**
1. Open browser to `http://localhost:8000`
2. Verify login page loads without errors
3. Check that VIvacity Master Calendar title is displayed
4. Verify "Sign in with Google" button is visible
5. Check page is responsive (resize browser window)

**Expected Results:**
- ✓ Login page loads cleanly
- ✓ Title and subtitle displayed correctly
- ✓ Google Sign-In button visible and styled
- ✓ No console errors
- ✓ Responsive design works

---

### Test 2: Google Sign-In Flow
**Steps:**
1. Click "Sign in with Google" button
2. Verify redirect to Google OAuth page
3. Sign in with your Google account (if not already signed in)
4. Grant permissions to the app
5. Verify redirect to `/dashboard.html`

**Expected Results:**
- ✓ Redirected to Google OAuth page
- ✓ User can sign in with Google account
- ✓ Permission screen shows app requesting email, profile info
- ✓ After authorization, redirected to dashboard
- ✓ User name displayed on dashboard
- ✓ User profile picture displayed (40x40px, circular)

---

### Test 3: Session Persistence
**Steps:**
1. Complete the login flow (Test 2)
2. Verify you're on `/dashboard.html` with user info displayed
3. Refresh the page (F5 or Cmd+R)
4. Verify you stay on dashboard (not redirected to login)

**Expected Results:**
- ✓ User stays authenticated after refresh
- ✓ User info still displayed
- ✓ No login page redirect

---

### Test 4: localStorage Verification
**Steps:**
1. Complete login flow
2. Open browser DevTools (F12)
3. Go to Application/Storage tab
4. Check Local Storage for `http://localhost:8000`
5. Look for `vivacity_user` key

**Expected Results:**
- ✓ `vivacity_user` key exists in localStorage
- ✓ Value contains user object with: name, email, profile_picture, user_id
- ✓ Data is valid JSON

---

### Test 5: Logout Functionality
**Steps:**
1. Complete login flow
2. Click on user profile area (top right)
3. Click "Sign Out" button
4. Verify redirect to `/index.html`

**Expected Results:**
- ✓ User dropdown menu appears on click
- ✓ "Sign Out" button visible in dropdown
- ✓ Clicking logout redirects to login page
- ✓ `vivacity_user` key removed from localStorage

---

### Test 6: Dashboard Protection
**Steps:**
1. Logout from the app (if logged in)
2. Clear all browser cookies
3. Clear localStorage (DevTools → Application → Local Storage)
4. Try to access `http://localhost:8000/dashboard.html` directly in URL bar

**Expected Results:**
- ✓ User is redirected to `/index.html`
- ✓ Loading spinner shown briefly while checking session
- ✓ Dashboard content not displayed
- ✓ User cannot access dashboard without authentication

---

### Test 7: Session Timeout
**Steps:**
1. Complete login flow
2. Wait 24 hours (session lifetime)
3. Refresh the page
4. Verify user is redirected to login

**Expected Results:**
- ✓ Session expires after 24 hours
- ✓ User redirected to login page
- ✓ localStorage cleared

---

### Test 8: No-JS Fallback
**Steps:**
1. Disable JavaScript in browser:
   - Chrome: Settings → Privacy → Site Settings → JavaScript → Blocked
2. Navigate to `http://localhost:8000`
3. Verify appropriate error message shown

**Expected Results:**
- ✓ "JavaScript is required" message displayed
- ✓ Google Sign-In button not shown
- ✓ Clear instruction to enable JavaScript

---

### Test 9: Responsive Design
**Steps:**
1. Open DevTools (F12)
2. Toggle device toolbar (Cmd+Shift+M or Ctrl+Shift+M)
3. Test different screen sizes:
   - Mobile: 375x667 (iPhone SE)
   - Tablet: 768x1024 (iPad)
   - Desktop: 1920x1080

**Expected Results:**
- ✓ Login card adjusts width appropriately
- ✓ Navbar stacks vertically on mobile
- ✓ User name hidden on mobile (shows only avatar)
- ✓ All text readable at all sizes
- ✓ No horizontal scrolling

---

### Test 10: Error Handling
**Steps:**
1. Simulate network error (disconnect from internet)
2. Try to access `/dashboard.html` while disconnected
3. Check console for error messages
4. Check for toast notifications

**Expected Results:**
- ✓ Error logged to console
- ✓ User-friendly toast notification shown
- ✓ App handles gracefully (doesn't crash)

---

### Test 11: OAuth State Validation
**Steps:**
1. Start login flow
2. Before completing, check sessionStorage for `oauth_state`
3. Verify state parameter is in OAuth URL

**Expected Results:**
- ✓ `oauth_state` key exists in sessionStorage
- ✓ State parameter present in OAuth authorization URL
- ✓ State is random string (not static)

---

### Test 12: Multiple Browser Tabs
**Steps:**
1. Open `http://localhost:8000` in Tab 1
2. Login and reach dashboard
3. Open `http://localhost:8000` in Tab 2
4. Verify Tab 2 redirects to dashboard (already logged in)
5. Logout from Tab 1
6. Refresh Tab 2

**Expected Results:**
- ✓ Tab 2 auto-redirects to dashboard (session shared)
- ✓ Logout in Tab 1 invalidates session for Tab 2
- ✓ Tab 2 redirects to login when refreshed

---

### Test 13: Browser Console Checks
**Steps:**
1. Open DevTools Console tab
2. Login and navigate through the app
3. Monitor for any errors or warnings

**Expected Results:**
- ✓ No red error messages
- ✓ Info messages from `auth.js` for debugging
- ✓ No CORS errors
- ✓ No 404 errors for resources

---

## Browser Compatibility Testing

Test on the following browsers (if available):

- [ ] Google Chrome 90+
- [ ] Firefox 88+
- [ ] Safari 14+
- [ ] Edge 90+

## Common Issues & Solutions

### Issue: Google Sign-In button not showing
**Solution:**
- Check browser console for errors
- Verify `/php/get_oauth_config.php` is accessible
- Check that Google Client ID is correct in config

### Issue: OAuth redirect fails
**Solution:**
- Verify redirect URI matches Google Cloud Console exactly
- Check that `http://localhost:8000/php/auth_callback.php` is in allowed origins
- Ensure OAuth config has correct client_id and redirect_uri

### Issue: Session check failing
**Solution:**
- Check that PHP sessions are working
- Verify `/php/auth.php` returns correct JSON
- Check browser cookies are enabled

### Issue: User not redirected after login
**Solution:**
- Check `/php/auth_callback.php` completes successfully
- Verify session is established
- Check browser network tab for failed requests

---

## Test Results Summary

Use this table to track test results:

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Login Page Display | ☐ Pass ☐ Fail | |
| 2 | Google Sign-In Flow | ☐ Pass ☐ Fail | |
| 3 | Session Persistence | ☐ Pass ☐ Fail | |
| 4 | localStorage Verification | ☐ Pass ☐ Fail | |
| 5 | Logout Functionality | ☐ Pass ☐ Fail | |
| 6 | Dashboard Protection | ☐ Pass ☐ Fail | |
| 7 | Session Timeout | ☐ Pass ☐ Fail | |
| 8 | No-JS Fallback | ☐ Pass ☐ Fail | |
| 9 | Responsive Design | ☐ Pass ☐ Fail | |
| 10 | Error Handling | ☐ Pass ☐ Fail | |
| 11 | OAuth State Validation | ☐ Pass ☐ Fail | |
| 12 | Multiple Browser Tabs | ☐ Pass ☐ Fail | |
| 13 | Browser Console Checks | ☐ Pass ☐ Fail | |

---

## Additional Notes

- All tests should pass before marking the task complete
- Document any issues found in the "Notes" column
- Take screenshots of the working application for documentation
- Test with both valid Google accounts and invalid scenarios
