<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
session_start();

// Check if user is logged in via session or token
$is_logged_in = isset($_SESSION['user_id']);

// If not logged in via session, check for client-side auth (token)
if (!$is_logged_in) {
    // We'll check client-side auth status with JavaScript
    $is_client_auth = true;
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Legal AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Legal AI Assistant</a>
            <div class="d-flex" id="navbarAuth">
                <!-- Auth UI will be updated by JavaScript -->
                <div id="logged-out-ui">
                    <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
                </div>
                
                <div id="client-logged-in-ui" style="display: none;">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userMenuClient" data-bs-toggle="dropdown">
                            <span id="usernameDisplay">User</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="my-files.php">My Files</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="logout-btn-client">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">User Profile</h3>
                    </div>
                    <div class="card-body">
                        <!-- Authentication check banner -->
                        <div id="auth-required-banner" class="alert alert-warning" style="display: none;">
                            <strong>Authentication Required</strong>
                            <p>You must be logged in to view your profile. Please <a href="index.php">return to the homepage</a> to log in.</p>
                        </div>
                        
                        <!-- Profile content (hidden until auth is verified) -->
                        <div id="profile-content" style="display: none;">
                            <div id="profile-alert" class="alert" style="display: none;"></div>
                            
                            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-pane" type="button" role="tab">Profile Details</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab">Change Password</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="profileTabContent">
                                <!-- Profile Details Tab -->
                                <div class="tab-pane fade show active" id="details-pane" role="tabpanel">
                                    <form id="profile-form">
                                        <div class="mb-3">
                                            <label for="profile-username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="profile-username" readonly>
                                            <div class="form-text">Username cannot be changed</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="profile-email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="profile-email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="profile-name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="profile-name">
                                        </div>
                                        <div class="mb-3">
                                            <label for="profile-phone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control" id="profile-phone">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                                
                                <!-- Change Password Tab -->
                                <div class="tab-pane fade" id="password-pane" role="tabpanel">
                                    <form id="password-form">
                                        <div class="mb-3">
                                            <label for="current-password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current-password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new-password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new-password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm-password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm-password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="login-alert" class="alert" style="display: none;"></div>
                    <form id="login-form">
                        <div class="mb-3">
                            <label for="login-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="login-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="login-password" required>
                        </div>
                        <div class="mb-3">
                            <a href="#" id="forgot-password-link">Forgot Password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="register-alert" class="alert" style="display: none;"></div>
                    <form id="register-form">
                        <div class="mb-3">
                            <label for="register-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="register-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="register-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="register-email" required>
                        </div>
                        <div class="mb-3">
                            <label for="register-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="register-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="register-confirm-password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="register-confirm-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/auth.js"></script>
    <script>
    // Add direct logout handler
    document.getElementById('logout-btn-client')?.addEventListener('click', function(e) {
        e.preventDefault();
        // Clear localStorage
        localStorage.removeItem('auth_token');
        localStorage.removeItem('username');
        localStorage.removeItem('validation_attempted');
        // Redirect to home page
        window.location.href = 'index.php';
    });
    
    document.addEventListener('DOMContentLoaded', async function() {
        // Check for token directly from localStorage
        const token = localStorage.getItem('auth_token');
        const username = localStorage.getItem('username');
        
        if (!token) {
            // Not authenticated, show auth required banner
            document.getElementById('auth-required-banner').style.display = 'block';
            return;
        }
        
        // User is authenticated, show profile content
        document.getElementById('profile-content').style.display = 'block';
        
        // Update UI with username if available
        if (username) {
            updateAuthUI(username);
        }
        
        // Load profile data
        try {
            const response = await fetch('api/profile.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load profile');
            }
            
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }
            
            // Populate form fields
            document.getElementById('profile-username').value = data.user.username;
            document.getElementById('profile-email').value = data.user.email || '';
            document.getElementById('profile-name').value = data.user.real_name || '';
            document.getElementById('profile-phone').value = data.user.phone || '';
            
            // Update UI auth state
            const username = data.user.username;
            localStorage.setItem('username', username);
            updateAuthUI(username);
            
        } catch (error) {
            const profileAlert = document.getElementById('profile-alert');
            profileAlert.className = 'alert alert-danger';
            profileAlert.textContent = `Error: ${error.message}`;
            profileAlert.style.display = 'block';
        }
        
        // Profile update form handler
        document.getElementById('profile-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const profileAlert = document.getElementById('profile-alert');
            
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('api/profile.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        email: document.getElementById('profile-email').value,
                        real_name: document.getElementById('profile-name').value,
                        phone: document.getElementById('profile-phone').value
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                profileAlert.className = 'alert alert-success';
                profileAlert.textContent = 'Profile updated successfully';
                profileAlert.style.display = 'block';
                
            } catch (error) {
                profileAlert.className = 'alert alert-danger';
                profileAlert.textContent = `Error: ${error.message}`;
                profileAlert.style.display = 'block';
            }
        });
        
        // Password change form handler
        document.getElementById('password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const profileAlert = document.getElementById('profile-alert');
            
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                profileAlert.className = 'alert alert-danger';
                profileAlert.textContent = 'New passwords do not match';
                profileAlert.style.display = 'block';
                return;
            }
            
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('api/profile.php?action=change-password', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        current_password: document.getElementById('current-password').value,
                        new_password: newPassword
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                // Clear password fields
                document.getElementById('current-password').value = '';
                document.getElementById('new-password').value = '';
                document.getElementById('confirm-password').value = '';
                
                profileAlert.className = 'alert alert-success';
                profileAlert.textContent = 'Password changed successfully';
                profileAlert.style.display = 'block';
                
            } catch (error) {
                profileAlert.className = 'alert alert-danger';
                profileAlert.textContent = `Error: ${error.message}`;
                profileAlert.style.display = 'block';
            }
        });
        
        // Helper function to update auth UI (copied from auth.js)
        function updateAuthUI(username) {
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
    });
    </script>
</body>
</html> 