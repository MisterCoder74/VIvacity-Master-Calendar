/**
 * Authentication Module
 * Handles Google Sign-In, session management, and authentication state
 */

const Auth = {
    /**
     * Check if user is authenticated by calling the backend session check endpoint
     * @returns {Promise<Object|null>} User object if authenticated, null otherwise
     */
    async checkSession() {
        try {
            const response = await fetch('php/auth.php', {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.error('Auth check failed:', response.statusText);
                return null;
            }

            const data = await response.json();

            if (data.authenticated && data.user) {
                // Store user data in localStorage for quick access
                localStorage.setItem('vivacity_user', JSON.stringify(data.user));
                return data.user;
            } else {
                // Ensure user stays on login page or clear localStorage
                localStorage.removeItem('vivacity_user');
                return null;
            }
        } catch (error) {
            console.error('Error checking session:', error);
            // Show user-friendly error message
            this.showToast('Error checking authentication. Please refresh.', 'error');
            return null;
        }
    },

    /**
     * Parse JWT token to extract user information
     * @param {string} token - JWT token to parse
     * @returns {Object} Decoded payload
     */
    parseJwt(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map((c) => {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        } catch (error) {
            console.error('Error parsing JWT:', error);
            throw new Error('Failed to parse authentication token');
        }
    },

    /**
     * Google Sign-In callback handler
     * Called when user clicks "Sign in with Google" button
     * @param {Object} response - Google Sign-In response containing JWT credential
     */
    async onSignIn(response) {
        try {
            // Extract user info from JWT token
            const payload = this.parseJwt(response.credential);
            const userInfo = {
                name: payload.name,
                email: payload.email,
                picture: payload.picture,
                google_id: payload.sub
            };

            console.log('Google user info:', userInfo);

            // Store user info temporarily in sessionStorage for the callback
            sessionStorage.setItem('temp_google_user', JSON.stringify(userInfo));

            // Build OAuth authorization URL
            // Note: We need to get the OAuth config values from the PHP config
            // Since we can't access PHP config directly, we'll redirect to a PHP endpoint
            // that will build and execute the redirect
            await this.redirectToGoogleAuth();
        } catch (error) {
            console.error('Error in onSignIn:', error);
            this.showToast('Sign-in failed. Please try again.', 'error');
        }
    },

    /**
     * Build OAuth authorization URL and redirect to Google
     * We need to fetch the OAuth config values from the server
     */
    async redirectToGoogleAuth() {
        try {
            // Fetch OAuth config from a PHP endpoint
            const response = await fetch('php/get_oauth_config.php');
            
            if (!response.ok) {
                throw new Error('Failed to get OAuth configuration');
            }

            const config = await response.json();

            // Generate random state for CSRF protection
            const state = Math.random().toString(36).substring(2, 15) + 
                         Math.random().toString(36).substring(2, 15);
            
            // Store state in sessionStorage for validation
            sessionStorage.setItem('oauth_state', state);

            // Build OAuth authorization URL
            const params = new URLSearchParams({
                client_id: config.client_id,
                redirect_uri: config.redirect_uri,
                response_type: 'code',
                scope: config.scope,
                state: state,
                prompt: 'select_account'
            });

            const authUrl = `${config.google_api_endpoint}?${params.toString()}`;

            // Redirect to Google OAuth
            window.location.href = authUrl;
        } catch (error) {
            console.error('Error redirecting to Google Auth:', error);
            this.showToast('Failed to start authentication process.', 'error');
        }
    },

    /**
     * Logout user - clear session and redirect to login
     */
    async logout() {
        try {
            // Call backend logout endpoint
            const response = await fetch('php/logout.php', {
                method: 'POST',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.error('Logout failed:', response.statusText);
            }

            // Clear localStorage
            localStorage.removeItem('vivacity_user');

            // Clear sessionStorage
            sessionStorage.clear();

            // Sign out from Google
            if (window.google && window.google.accounts) {
                google.accounts.id.disableAutoSignIn();
            }

            // Redirect to login page
            window.location.href = 'index.html';
        } catch (error) {
            console.error('Error during logout:', error);
            // Even if there's an error, clear local storage and redirect
            localStorage.removeItem('vivacity_user');
            sessionStorage.clear();
            window.location.href = 'index.html';
        }
    },

    /**
     * Initialize authentication - check session and redirect if needed
     */
    async initializeAuth() {
        try {
            // Check if user is already authenticated
            const user = await this.checkSession();

            if (user) {
                // User is authenticated, redirect to dashboard
                window.location.href = 'dashboard.html';
            } else {
                // User is not authenticated, stay on login page
                // Initialize Google Sign-In library
                this.initializeGoogleSignIn();
            }
        } catch (error) {
            console.error('Error initializing authentication:', error);
            this.showToast('Error initializing authentication. Please refresh.', 'error');
        }
    },

    /**
     * Initialize Google Sign-In library
     */
    initializeGoogleSignIn() {
        try {
            // Google Sign-In library is loaded asynchronously
            // We need to wait for it to be ready
            const checkGoogleReady = setInterval(() => {
                if (window.google && window.google.accounts && window.google.accounts.id) {
                    clearInterval(checkGoogleReady);

                    // Fetch OAuth config to get client ID
                    fetch('php/get_oauth_config.php')
                        .then(response => response.json())
                        .then(config => {
                            // Initialize Google Sign-In
                            google.accounts.id.initialize({
                                client_id: config.client_id,
                                callback: this.onSignIn.bind(this),
                                auto_prompt: false,
                                cancel_on_tap_outside: false
                            });

                            // Render the button
                            google.accounts.id.renderButton(
                                document.getElementById('google-signin-button'),
                                {
                                    theme: 'outline',
                                    size: 'large',
                                    text: 'signin_with',
                                    width: 250,
                                    logo_alignment: 'center'
                                }
                            );
                        })
                        .catch(error => {
                            console.error('Error loading OAuth config:', error);
                            this.showToast('Failed to load Google Sign-In.', 'error');
                        });
                }
            }, 100);

            // Timeout after 10 seconds
            setTimeout(() => {
                clearInterval(checkGoogleReady);
                if (!window.google) {
                    console.error('Google Sign-In library failed to load');
                    this.showToast('Google Sign-In failed to load. Please refresh.', 'error');
                }
            }, 10000);
        } catch (error) {
            console.error('Error initializing Google Sign-In:', error);
            this.showToast('Failed to initialize Google Sign-In.', 'error');
        }
    },

    /**
     * Show toast notification
     * @param {string} message - Message to display
     * @param {string} type - Type: 'success' or 'error'
     */
    showToast(message, type = 'success') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast');
        existingToasts.forEach(toast => toast.remove());

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    },

    /**
     * Get current user from localStorage
     * @returns {Object|null} User object or null
     */
    getCurrentUser() {
        try {
            const userStr = localStorage.getItem('vivacity_user');
            return userStr ? JSON.parse(userStr) : null;
        } catch (error) {
            console.error('Error getting current user:', error);
            return null;
        }
    },

    /**
     * Toggle user dropdown menu
     */
    toggleUserDropdown() {
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    },

    /**
     * Close user dropdown menu
     */
    closeUserDropdown() {
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    }
};

// Close dropdown when clicking outside
document.addEventListener('click', (event) => {
    const dropdown = document.getElementById('user-dropdown');
    const toggle = document.querySelector('.user-profile-toggle');
    
    if (dropdown && !dropdown.contains(event.target) && !toggle.contains(event.target)) {
        Auth.closeUserDropdown();
    }
});
