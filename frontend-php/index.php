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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
                                <li><a class="dropdown-item" href="#" id="save-chat-btn" data-bs-toggle="modal" data-bs-target="#emailChatModal">Save chat to email</a></li>
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
                            <li><a class="dropdown-item" href="#" id="save-chat-btn-client" data-bs-toggle="modal" data-bs-target="#emailChatModal">Save chat to email</a></li>
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0">Tenant Rights Assistant</h3>
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    <small>Ask a question or log in to save your chat history</small>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="clear-chat-btn" class="btn btn-sm btn-outline-light">
                                <i class="bi bi-trash"></i> Clear Chat
                            </button>
                        </div>
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

    <!-- Email Chat Modal -->
    <div class="modal fade" id="emailChatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="modal-title">Save Chat to Email</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="email-chat-alert" class="alert" style="display: none;"></div>
                    <form id="email-chat-form">
                        <div class="mb-3">
                            <label for="email-chat-address" class="form-label">Your Email Address</label>
                            <input type="email" class="form-control" id="email-chat-address" required>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-chat-dots"></i> Return to Chat
                            </a>
                            <div>
                                <button type="button" id="email-chat-send" class="btn btn-primary me-2">Email Chat</button>
                                <button type="button" id="email-chat-send-end" class="btn btn-danger">Email & End Session</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fix for logout-on-refresh issue - ONLY modify the validation mechanism
        window.addEventListener('load', function() {
            // Clear the validation attempted flag to prevent it from blocking validation
            sessionStorage.removeItem('validation_attempted');
            
            // Store that we've seen this page to prevent redirect loops
            if (!sessionStorage.getItem('page_viewed')) {
                sessionStorage.setItem('page_viewed', 'true');
            }
        });
    </script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/chat.js?v=1.1.0"></script>
    <script>
        // Email chat functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Fill email field with user's email on modal open
            const emailChatModal = document.getElementById('emailChatModal');
            if (emailChatModal) {
                emailChatModal.addEventListener('show.bs.modal', async function() {
                    const token = localStorage.getItem('auth_token');
                    if (token) {
                        try {
                            // Try to get user's email from profile
                            const response = await fetch('api/profile.php', {
                                headers: { 'Authorization': `Bearer ${token}` }
                            });
                            
                            if (response.ok) {
                                const data = await response.json();
                                if (data.success && data.user.email) {
                                    document.getElementById('email-chat-address').value = data.user.email;
                                }
                            }
                        } catch (error) {
                            console.error('Error fetching user email:', error);
                        }
                    }
                });
            }
            
            // Function to send chat via email
            async function sendChatEmail(endSession = false) {
                const emailAlert = document.getElementById('email-chat-alert');
                const emailAddress = document.getElementById('email-chat-address').value;
                
                if (!emailAddress) {
                    emailAlert.className = 'alert alert-danger';
                    emailAlert.textContent = 'Please enter an email address';
                    emailAlert.style.display = 'block';
                    return;
                }
                
                // Get chat messages from DOM
                const chatMessages = document.getElementById('chat-messages');
                const chatContent = chatMessages ? chatMessages.innerHTML : '';
                
                try {
                    // Show sending indicator
                    emailAlert.className = 'alert alert-info';
                    emailAlert.textContent = 'Sending email...';
                    emailAlert.style.display = 'block';
                    
                    // Send chat to server-side email handler
                    const response = await fetch('api/email-chat.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                        },
                        body: JSON.stringify({
                            email: emailAddress,
                            content: chatContent
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Show success message
                        emailAlert.className = 'alert alert-success';
                        emailAlert.textContent = 'Chat history sent to your email successfully!';
                        
                        // If endSession is true, log the user out
                        if (endSession) {
                            setTimeout(function() {
                                // Clear chat window first
                                const chatMessages = document.getElementById('chat-messages');
                                if (chatMessages) {
                                    chatMessages.innerHTML = '';
                                }
                                
                                // Clear auth data and chat history
                                localStorage.removeItem('auth_token');
                                localStorage.removeItem('username');
                                localStorage.removeItem('validation_attempted');
                                localStorage.removeItem('chat_history');
                                
                                // Redirect to home & refresh
                                window.location.href = 'index.php';
                            }, 1500);
                        }
                    } else {
                        throw new Error(data.message || 'Failed to send email');
                    }
                } catch (error) {
                    emailAlert.className = 'alert alert-danger';
                    emailAlert.textContent = `Error sending email: ${error.message}`;
                }
            }
            
            // Email chat handlers
            document.getElementById('email-chat-send')?.addEventListener('click', function() {
                sendChatEmail(false);
            });
            
            document.getElementById('email-chat-send-end')?.addEventListener('click', function() {
                sendChatEmail(true);
            });
            
            // Clear chat button handler
            document.getElementById('clear-chat-btn')?.addEventListener('click', function() {
                if (window.clearChatHistory) {
                    window.clearChatHistory();
                } else {
                    // Fallback if clearChatHistory isn't available
                    const chatMessages = document.getElementById('chat-messages');
                    if (chatMessages) {
                        chatMessages.innerHTML = '';
                    }
                    localStorage.removeItem('chat_history');
                    // Don't try to access conversationHistory in this scope
                }
            });
        });
    </script>
</body>
</html>  