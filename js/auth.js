/**
 * Authentication Module - Minimal Version
 * Handles Google Sign-In and session management
 */

const Auth = {
    /**
     * Initialize authentication
     */
    initializeAuth() {
        this.initializeGoogleSignIn();
    },

    /**
     * Initialize Google Sign-In library
     */
    initializeGoogleSignIn() {
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
                    });
            }
        }, 100);

        // Timeout after 10 seconds
        setTimeout(() => {
            clearInterval(checkGoogleReady);
        }, 10000);
    },

    /**
     * Parse JWT token to extract user information
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

            // Store user info temporarily in sessionStorage
            sessionStorage.setItem('temp_google_user', JSON.stringify(userInfo));

            // Build OAuth authorization URL
            await this.redirectToGoogleAuth();
        } catch (error) {
            console.error('Error in onSignIn:', error);
        }
    },

    /**
     * Build OAuth authorization URL and redirect to Google
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
                scope: 'openid email profile',
                state: state,
                prompt: 'select_account'
            });

            const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?${params.toString()}`;

            // Redirect to Google OAuth
            window.location.href = authUrl;
        } catch (error) {
            console.error('Error redirecting to Google Auth:', error);
        }
    }
};
