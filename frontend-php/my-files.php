<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
session_start();

// Check if user is logged in via session
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Files - Legal AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Legal AI Assistant</a>
            <div class="d-flex" id="navbarAuth">
                <!-- Server-rendered auth state -->
                <?php if (!$is_logged_in): ?>
                    <div id="logged-out-ui">
                        <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                        <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
                    </div>
                <?php else: ?>
                    <div id="logged-in-ui">
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($username); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="my-files.php">My Files</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" id="logout-btn">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Client-side auth state (hidden by default) -->
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
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">My Files</h3>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-upload"></i> Upload New File
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Authentication check banner -->
                        <div id="auth-required-banner" class="alert alert-warning" style="display: none;">
                            <strong>Authentication Required</strong>
                            <p>You must be logged in to view your files. Please <a href="index.php">return to the homepage</a> to log in.</p>
                        </div>
                        
                        <!-- Files content (hidden until auth is verified) -->
                        <div id="files-content" style="display: none;">
                            <div id="files-alert" class="alert" style="display: none;"></div>
                            
                            <!-- File search and filter options -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="file-search" placeholder="Search files...">
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
                                        <button type="button" class="btn btn-outline-secondary" data-filter="documents">Documents</button>
                                        <button type="button" class="btn btn-outline-secondary" data-filter="images">Images</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Files list -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Actions</th>
                                            <th>Filename</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Uploaded</th>
                                        </tr>
                                    </thead>
                                    <tbody id="files-list">
                                        <!-- Files will be loaded here -->
                                        <tr id="no-files-message">
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-folder2-open" style="font-size: 2rem;"></i>
                                                    <p class="mt-2">No files uploaded yet. Click "Upload New File" to get started.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload File Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="upload-alert" class="alert" style="display: none;"></div>
                    <form id="upload-form" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="file-upload" class="form-label">Choose a file</label>
                            <input type="file" class="form-control" id="file-upload" name="file" required>
                            <div class="form-text">Maximum file size: 10MB</div>
                        </div>
                        <div class="mb-3">
                            <label for="file-description" class="form-label">Description (optional)</label>
                            <textarea class="form-control" id="file-description" name="description" rows="3"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
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
                            <label for="register-fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="register-fullname">
                        </div>
                        <div class="mb-3">
                            <label for="register-phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="register-phone">
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
        // Immediately check auth status and update UI before files.js loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we have a token
            const token = localStorage.getItem('auth_token');
            const username = localStorage.getItem('username');
            
            if (token && username) {
                // Hide logged-out UI
                const loggedOutUI = document.getElementById('logged-out-ui');
                if (loggedOutUI) {
                    loggedOutUI.style.display = 'none';
                }
                
                // Hide server-side logged-in UI if present (will use client-side UI)
                const loggedInUI = document.getElementById('logged-in-ui');
                if (loggedInUI) {
                    loggedInUI.style.display = 'none';
                }
                
                // Show client-side logged-in UI
                const clientLoggedInUI = document.getElementById('client-logged-in-ui');
                if (clientLoggedInUI) {
                    clientLoggedInUI.style.display = 'block';
                    
                    // Update username display
                    const usernameDisplay = document.getElementById('usernameDisplay');
                    if (usernameDisplay) {
                        usernameDisplay.textContent = username;
                    }
                }
            }
        });
    </script>
    <script src="assets/js/files.js"></script>
</body>
</html> 