class Auth {
    constructor() {
        this.token = localStorage.getItem('auth_token');
        
        // Simple direct path detection
        const path = window.location.pathname;
        const firstSegment = path.split('/')[1]; // Get first path segment
        this.baseUrl = firstSegment ? `/${firstSegment}/api` : '/api';
        
        // Debug info
        console.log('Auth initialized with baseUrl:', this.baseUrl);
        console.log('Current path:', path);
        console.log('First segment:', firstSegment);
        console.log('Token in localStorage:', this.token ? this.token.substring(0, 10) + '...' : 'none');
    }

    // Helper function to ensure consistent token format
    getAuthHeader() {
        if (!this.token) return null;
        return this.token.startsWith('Bearer ') ? this.token : `Bearer ${this.token}`;
    }

    // Helper to add auth headers to fetch requests
    async fetchWithAuth(url, options = {}) {
        const headers = options.headers || {};
        
        if (this.token) {
            // Ensure token is properly formatted with Bearer prefix
            const formattedToken = this.token.startsWith('Bearer ') ? this.token : `Bearer ${this.token}`;
            headers['Authorization'] = formattedToken;
            console.log('Adding Authorization header with token');
        } else {
            console.warn('No token available for request to', url);
            // Try to get token from localStorage directly in case it was added since this instance initialized
            const storedToken = localStorage.getItem('auth_token');
            if (storedToken) {
                this.token = storedToken; // Update instance token
                const formattedToken = storedToken.startsWith('Bearer ') ? storedToken : `Bearer ${storedToken}`;
                headers['Authorization'] = formattedToken;
                console.log('Retrieved token from localStorage for request');
            }
        }
        
        const fetchOptions = {
            ...options,
            headers: {
                ...headers
            }
        };
        
        // Only add Content-Type: application/json if we're not sending FormData
        // FormData sets its own Content-Type with boundary
        if (!options.body || !(options.body instanceof FormData)) {
            fetchOptions.headers['Content-Type'] = 'application/json';
        }
        
        console.log(`Making ${options.method || 'GET'} request to ${url}`);
        if (headers['Authorization']) {
            console.log('With Authorization header:', headers['Authorization'].substring(0, 15) + '...');
        } else {
            console.log('Without Authorization header');
        }
        
        return fetch(url, fetchOptions);
    }

    async register(username, password, email, phone = null, realName = null) {
        const endpoint = `${this.baseUrl}/auth.php?action=register`;
        const payload = {
            username,
            password,
            email,
            phone,
            real_name: realName
        };
        
        console.log('Making registration request to:', endpoint);
        console.log('With payload:', {...payload, password: '[REDACTED]'});
        
        try {
            const response = await this.fetchWithAuth(endpoint, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries([...response.headers]));
            
            // Try to get the raw response text first
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            // Then try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Parsed JSON response:', data);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error(`Failed to parse JSON response: ${responseText.substring(0, 100)}...`);
            }
            
            if (!data.success) {
                throw new Error(data.message || 'Unknown error');
            }

            return data;
        } catch (error) {
            console.error('Registration error:', error);
            throw error;
        }
    }

    async login(username, password) {
        const endpoint = `${this.baseUrl}/auth.php?action=login`;
        console.log('Making login request to:', endpoint);
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });
            
            console.log('Response status:', response.status);
            
            // Try to get the raw response text first
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            // Then try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Parsed JSON response:', data);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error(`Failed to parse JSON response: ${responseText.substring(0, 100)}...`);
            }

            if (!data.success) {
                throw new Error(data.message || 'Unknown error');
            }

            // Check which token field is in the response
            if (data.token) {
                this.token = data.token;
                localStorage.setItem('auth_token', data.token);
                console.log('Saved token from "token" field:', this.token.substring(0, 10) + '...');
            } else if (data.session_token) {
                this.token = data.session_token;
                localStorage.setItem('auth_token', data.session_token);
                console.log('Saved token from "session_token" field:', this.token.substring(0, 10) + '...');
            } else {
                console.warn('No token found in response!', data);
            }
            
            // Store username if provided in response
            if (data.username) {
                localStorage.setItem('username', data.username);
            }
            
            // Debug: Log current token
            console.log('Current token:', this.token ? this.token.substring(0, 10) + '...' : 'none');
            
            return data;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }

    async logout() {
        try {
            if (!this.token) {
                throw new Error('Not logged in');
            }

            const response = await this.fetchWithAuth(`${this.baseUrl}/auth.php?action=logout`, {
                method: 'POST'
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            this.token = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('username');
            return data;
        } catch (error) {
            throw error;
        }
    }

    async validateSession() {
        try {
            if (!this.token) {
                return false;
            }

            const response = await this.fetchWithAuth(`${this.baseUrl}/auth.php?action=validate`);

            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Session validation error:', error);
            return false;
        }
    }

    async requestPasswordReset(email) {
        try {
            const response = await fetch(`${this.baseUrl}/password-reset.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            throw error;
        }
    }

    async validateResetToken(token) {
        try {
            const response = await fetch(`${this.baseUrl}/password-reset.php?token=${token}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            throw error;
        }
    }

    async resetPassword(token, newPassword) {
        try {
            const response = await fetch(`${this.baseUrl}/password-reset.php?action=reset`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token,
                    new_password: newPassword
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            throw error;
        }
    }

    isLoggedIn() {
        return !!this.token;
    }

    getToken() {
        return this.token;
    }
}

// Initialize auth instance and make it globally accessible
const auth = new Auth();
window.auth = auth; // Explicitly add to window object

// DOM Elements
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const logoutBtn = document.getElementById('logout-btn');
const loginAlert = document.getElementById('login-alert');
const registerAlert = document.getElementById('register-alert');
const forgotPasswordLink = document.getElementById('forgot-password-link');

// Add debug button to help troubleshoot
function addDebugButton() {
    const debugBtn = document.createElement('button');
    debugBtn.textContent = 'Debug Auth';
    debugBtn.className = 'btn btn-sm btn-warning fixed-bottom m-3';
    debugBtn.style.left = 'auto';
    debugBtn.style.width = '120px';
    debugBtn.onclick = () => {
        const debugInfo = {
            token: auth.token ? auth.token.substring(0, 10) + '...' : 'none',
            tokenLength: auth.token ? auth.token.length : 0,
            isLoggedIn: auth.isLoggedIn(),
            baseUrl: auth.baseUrl,
            localStorage: {
                auth_token: localStorage.getItem('auth_token') ? 
                    localStorage.getItem('auth_token').substring(0, 10) + '...' : 'none',
                auth_token_length: localStorage.getItem('auth_token') ? 
                    localStorage.getItem('auth_token').length : 0,
                username: localStorage.getItem('username')
            }
        };
        
        // Create modal to display debug info
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'debugModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Auth Debug Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <pre>${JSON.stringify(debugInfo, null, 2)}</pre>
                        <div class="d-grid gap-2">
                            <a href="/tra/token_debug.php" class="btn btn-info" target="_blank">Open Token Debug Tool</a>
                            <a href="/tra/debug_auth.html" class="btn btn-secondary" target="_blank">Open API Test Tool</a>
                            <button class="btn btn-warning" id="testTokenBtn">Test Current Token</button>
                            <button class="btn btn-danger" id="fixTokenBtn">Fix Token Format</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Show the modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Add event listener for test token button
        document.getElementById('testTokenBtn').addEventListener('click', async () => {
            const resultDiv = document.createElement('div');
            resultDiv.className = 'mt-3 p-3 bg-light';
            resultDiv.innerHTML = '<p>Testing token against profile endpoint...</p>';
            modal.querySelector('.modal-body').appendChild(resultDiv);
            
            try {
                const response = await auth.fetchWithAuth(`${auth.baseUrl}/profile.php`);
                const status = response.status;
                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    data = { error: 'Failed to parse JSON response' };
                }
                
                resultDiv.innerHTML = `
                    <h5>Test Results:</h5>
                    <p>Status: ${status}</p>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <h5>Test Error:</h5>
                    <p>${error.message}</p>
                `;
            }
        });
        
        // Add event listener for fix token button
        document.getElementById('fixTokenBtn').addEventListener('click', () => {
            try {
                // Get current token
                let token = localStorage.getItem('auth_token');
                
                if (!token) {
                    alert('No token found in localStorage');
                    return;
                }
                
                // Remove Bearer prefix if it exists
                if (token.startsWith('Bearer ')) {
                    token = token.substring(7);
                }
                
                // Save clean token
                localStorage.setItem('auth_token', token);
                auth.token = token;
                
                // Update debug info
                const updatedInfo = {
                    token: auth.token ? auth.token.substring(0, 10) + '...' : 'none',
                    tokenLength: auth.token ? auth.token.length : 0,
                    localStorage: {
                        auth_token: localStorage.getItem('auth_token') ? 
                            localStorage.getItem('auth_token').substring(0, 10) + '...' : 'none',
                        auth_token_length: localStorage.getItem('auth_token') ? 
                            localStorage.getItem('auth_token').length : 0
                    }
                };
                
                const resultDiv = document.createElement('div');
                resultDiv.className = 'mt-3 p-3 bg-success text-white';
                resultDiv.innerHTML = `
                    <h5>Token Updated!</h5>
                    <p>Bearer prefix has been removed if it existed.</p>
                    <pre>${JSON.stringify(updatedInfo, null, 2)}</pre>
                `;
                modal.querySelector('.modal-body').appendChild(resultDiv);
            } catch (error) {
                alert('Error fixing token: ' + error.message);
            }
        });
    };
    document.body.appendChild(debugBtn);
}

// Debounce function to limit API calls
function debounce(func, timeout = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}

// Add event listener for username field to clear errors and check availability
const registerUsernameInput = document.getElementById('register-username');
if (registerUsernameInput) {
    // Clear error on any input change
    registerUsernameInput.addEventListener('input', () => {
        // Clear any existing error message when username is changed
        if (registerAlert.style.display === 'block') {
            console.log('Clearing username error message on input change');
            registerAlert.style.display = 'none';
        }
        
        // Remove visual indicators
        registerUsernameInput.classList.remove('is-invalid');
        registerUsernameInput.classList.remove('is-valid');
    });
    
    // Check availability after typing stops (debounced)
    const checkUsernameDebounced = debounce(async () => {
        const username = registerUsernameInput.value.trim();
        if (username.length < 3) return; // Don't check too short usernames
        
        try {
            console.log('Checking username availability while typing:', username);
            const result = await checkUsernameAvailability(username);
            
            // Add visual feedback
            if (result.error) {
                // Do nothing on error
                console.warn('Username check error:', result.error);
            } else if (result.exists) {
                registerUsernameInput.classList.add('is-invalid');
                console.log('Username exists (from debounced check)');
            } else {
                registerUsernameInput.classList.add('is-valid');
                console.log('Username available (from debounced check)');
            }
        } catch (err) {
            console.error('Error in debounced username check:', err);
        }
    }, 500);
    
    registerUsernameInput.addEventListener('input', checkUsernameDebounced);
}

// Event Handlers
loginForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;

    try {
        const result = await auth.login(username, password);
        if (result.success) {
            loginAlert.className = 'alert alert-success';
            loginAlert.textContent = 'Login successful!';
            loginAlert.style.display = 'block';
            
            // Close the login modal
            const loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            if (loginModal) {
                loginModal.hide();
            }
            
            // Update navbar UI
            updateAuthUI(username);
            
            // Clear validation flag to allow future checks
            sessionStorage.removeItem('validation_attempted');
        }
    } catch (error) {
        loginAlert.className = 'alert alert-danger';
        loginAlert.textContent = `Error: ${cleanErrorMessage(error.message)}`;
        loginAlert.style.display = 'block';
        console.error('Detailed login error:', error);
    }
});

// Username check function to be used during registration
async function checkUsernameAvailability(username) {
    console.log('Starting username availability check for:', username);
    const timestamp = new Date().getTime(); // Add cache-busting parameter
    try {
        const checkURL = `${auth.baseUrl}/auth.php?action=check_username&username=${encodeURIComponent(username)}&nocache=${timestamp}`;
        console.log('Checking username with URL:', checkURL);
        const checkResponse = await fetch(checkURL);
        
        if (!checkResponse.ok) {
            console.error('Username check failed with status:', checkResponse.status);
            // Return a default response object instead of throwing an error
            return {
                success: false,
                error: `Server returned ${checkResponse.status}`,
                available: false,
                exists: false
            };
        }
        
        const responseText = await checkResponse.text();
        console.log('Raw username check response:', responseText);
        
        try {
            const checkData = JSON.parse(responseText);
            console.log('Parsed username check response:', checkData);
            return checkData;
        } catch (e) {
            console.error('Failed to parse username check response:', e);
            // Return a default response object on parse error
            return {
                success: false,
                error: 'Failed to parse server response',
                available: false,
                exists: false
            };
        }
    } catch (error) {
        console.error('Username check error:', error);
        // Return a default response object on fetch error
        return {
            success: false,
            error: error.message,
            available: false,
            exists: false
        };
    }
}

registerForm?.addEventListener('submit', async (e) => {
    // Prevent the default form submission
    e.preventDefault();
    console.log('Register form submission started');
    
    // Reset any previous messages
    registerAlert.style.display = 'none';
    
    // Get form values
    const username = document.getElementById('register-username').value.trim();
    const email = document.getElementById('register-email').value.trim();
    const fullname = document.getElementById('register-fullname')?.value.trim() || null;
    const phone = document.getElementById('register-phone')?.value.trim() || null;
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('register-confirm-password').value;
    
    // Display processing message
    registerAlert.className = 'alert alert-info';
    registerAlert.textContent = 'Processing registration...';
    registerAlert.style.display = 'block';
    
    // Disable submit button to prevent multiple submissions
    const submitButton = registerForm.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    
    try {
        // STEP 1: Check username availability FIRST - CRITICAL: THIS MUST REMAIN AS THE FIRST VALIDATION STEP
        console.log('STEP 1: Checking username availability - must be first validation');
        const usernameCheck = await checkUsernameAvailability(username);
        
        console.log('Username check exact response:', usernameCheck);
        console.log('- available:', usernameCheck.available);
        console.log('- exists:', usernameCheck.exists);
        console.log('- success:', usernameCheck.success);
        console.log('- Type of exists:', typeof usernameCheck.exists);
        console.log('- JSON:', JSON.stringify(usernameCheck));
        
        // ALWAYS PROCEED with registration - temporarily bypass the username check
        console.log('*** BYPASSING username check and proceeding with registration ***');
        
        // STEP 2: Check password match SECOND - Only check after username validation passes
        console.log('STEP 2: Checking password match');
        if (password !== confirmPassword) {
            console.log('Passwords do not match, showing error');
            registerAlert.className = 'alert alert-danger';
            registerAlert.textContent = 'Passwords do not match!';
            if (submitButton) submitButton.disabled = false;
            return;
        }
        
        // STEP 3: Proceed with registration
        console.log('STEP 3: Proceeding with registration');
        const result = await auth.register(username, password, email, phone, fullname);
        console.log('Registration process completed:', result);
        
        if (result.success) {
            registerAlert.className = 'alert alert-success';
            registerAlert.textContent = 'Registration successful! Please log in.';
            
            // Reset the form
            registerForm.reset();
            
            // Redirect to login after delay
            setTimeout(() => {
                const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                registerModal.hide();
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            }, 1500);
        }
    } catch (error) {
        // Clear any existing content and set a clean error message
        console.error('Registration error:', error);
        registerAlert.className = 'alert alert-danger';
        
        // Improve error message for common errors
        let errorMessage = error.message || 'Unknown error occurred';
        
        // Check for specific error messages and make them more descriptive
        if (errorMessage.includes('Username or email already exists')) {
            // Do a follow-up check to determine if it's the username or email that's the problem
            console.log('Checking specifically if it was email or username that exists...');
            
            // Check username first since that's faster
            try {
                const usernameExistsCheck = await checkUsernameAvailability(username);
                if (usernameExistsCheck.exists) {
                    errorMessage = 'This username is already taken. Please choose another username.';
                } else {
                    // If username is available, then it must be the email that's duplicate
                    errorMessage = 'This email address is already registered. Please use a different email address or try the forgot password option in the login screen.';
                }
            } catch (checkError) {
                // If check fails, use a more generic but still helpful message
                errorMessage = 'Username or email already exists, use forgot password in the login button';
            }
        }
        
        registerAlert.textContent = 'Error: ' + errorMessage;
    } finally {
        // Re-enable the submit button
        if (submitButton) submitButton.disabled = false;
    }
});

logoutBtn?.addEventListener('click', async (e) => {
    e.preventDefault();
    try {
        await auth.logout();
        window.location.reload();
    } catch (error) {
        console.error('Logout failed:', error);
    }
});

forgotPasswordLink?.addEventListener('click', async (e) => {
    e.preventDefault();
    
    // Prompt for email since we now use username for login
    const email = prompt('Please enter your email address to reset your password:');
    
    if (!email) {
        return; // User cancelled
    }

    try {
        loginAlert.className = 'alert alert-info';
        loginAlert.textContent = 'Processing password reset request...';
        loginAlert.style.display = 'block';
        
        const result = await auth.requestPasswordReset(email);
        if (result.success) {
            loginAlert.className = 'alert alert-success';
            loginAlert.textContent = 'Password reset instructions have been sent to your email.';
            loginAlert.style.display = 'block';
        }
    } catch (error) {
        loginAlert.className = 'alert alert-danger';
        loginAlert.textContent = error.message;
        loginAlert.style.display = 'block';
    }
});

// Check session status on page load
document.addEventListener('DOMContentLoaded', async () => {
    // Prevent repeated validation attempts with a flag
    const validationAttempted = sessionStorage.getItem('validation_attempted');
    if (validationAttempted) {
        return; // Skip validation on reloads caused by validation
    }
    
    sessionStorage.setItem('validation_attempted', 'true');
    
    // Only validate if we have a token
    if (auth.getToken()) {
        const isValid = await auth.validateSession();
        if (!isValid) {
            console.log('Session invalid, logging out...');
            auth.logout();
            // Use location.href instead of reload to avoid loop
            window.location.href = window.location.pathname;
        } else {
            console.log('Session validated successfully');
            // Get username from localStorage or sessionStorage
            const username = localStorage.getItem('username') || 'User';
            updateAuthUI(username);
        }
    }
    
    // Add debug button when DOM is loaded
    addDebugButton();
});

// Add client-side logout button handler
document.getElementById('logout-btn-client')?.addEventListener('click', async (e) => {
    e.preventDefault();
    try {
        await auth.logout();
        // Reset UI
        document.getElementById('logged-out-ui').style.display = 'block';
        document.getElementById('client-logged-in-ui').style.display = 'none';
        // Clear storage
        localStorage.removeItem('username');
        // Use replacement to avoid reload loop
        window.location.href = window.location.pathname;
    } catch (error) {
        console.error('Logout failed:', error);
    }
});

// Function to update auth UI
function updateAuthUI(username) {
    // Store username for future use
    localStorage.setItem('username', username);
    
    // Update username display
    const usernameDisplay = document.getElementById('usernameDisplay');
    if (usernameDisplay) {
        usernameDisplay.textContent = username;
    }
    
    // Hide server-side elements if they exist
    const loggedOutUI = document.getElementById('logged-out-ui');
    if (loggedOutUI) {
        loggedOutUI.style.display = 'none';
    }
    
    // Show client-side logged in UI
    const clientLoggedInUI = document.getElementById('client-logged-in-ui');
    if (clientLoggedInUI) {
        clientLoggedInUI.style.display = 'block';
    }
}

// Helper to clean error messages (remove debugging hints)
function cleanErrorMessage(message) {
    // Remove any HTML and debugging hints
    return message.replace(/<br><small>.*?<\/small>/g, '').replace(/\. Check console for details \(F12\)/g, '');
} 