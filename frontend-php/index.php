<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
session_start();

// Initialize user session if not exists
if (!isset($_SESSION['user_id'])) {
    $_SESSION['is_anonymous'] = true;
    $_SESSION['chat_history'] = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Rights Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Legal AI Assistant</a>
            <div class="d-flex" id="navbarAuth">
                <!-- Server-rendered auth state -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div id="logged-out-ui">
                        <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                        <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
                    </div>
                <?php else: ?>
                    <div id="logged-in-ui">
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
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
            <div class="col-md-8">
                <!-- Database Test Button - Temporarily hidden
                <!--
                <div class="text-end mb-3">
                    <button id="testDb" class="btn btn-secondary btn-sm">Test Database Connection</button>
                </div>
                <div id="dbStatus" class="alert" style="display: none;"></div>
                -->
                
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Tenant Rights Assistant</h3>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <small>Ask a question or log in to save your chat history</small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div id="chat-messages" class="chat-container mb-4">
                            <!-- Messages will be inserted here -->
                        </div>
                        <form id="chat-form" class="chat-input">
                            <div class="input-group">
                                <input type="text" id="message-input" class="form-control" placeholder="Type your question here..." required>
                                <button type="submit" class="btn btn-primary">Send</button>
                            </div>
                        </form>
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
                            <label for="register-username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="register-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="register-email" class="form-label">Email <span class="text-danger">*</span></label>
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
                            <label for="register-password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="register-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="register-confirm-password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="register-confirm-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    /* Database test functionality - temporarily disabled
    document.getElementById('testDb').addEventListener('click', function() {
        const statusDiv = document.getElementById('dbStatus');
        statusDiv.style.display = 'none';
        
        fetch('test_db.php')
            .then(response => response.json())
            .then(data => {
                statusDiv.className = 'alert ' + (data.status === 'success' ? 'alert-success' : 'alert-danger');
                let message = data.message;
                if (data.status === 'success') {
                    message += '<br>PHP Version: ' + data.details.php_version;
                    message += '<br>MySQL Version: ' + data.details.mysql_version;
                    message += '<br>Server Time: ' + data.details.server_time;
                } else {
                    message += '<br>Error: ' + data.error;
                }
                statusDiv.innerHTML = message;
                statusDiv.style.display = 'block';
            })
            .catch(error => {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = 'Failed to test database connection: ' + error.message;
                statusDiv.style.display = 'block';
            });
    });
    */
    </script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/chat.js"></script>
</body>
</html> 