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
                        <?php if ($_SESSION['is_anonymous']): ?>
                            <small>Ask a question or <a href="#" class="text-white text-decoration-underline" data-bs-toggle="modal" data-bs-target="#loginModal">log in</a> if you have an ID</small>
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
                    <form id="login-form">
                        <div class="mb-3">
                            <label for="user-id" class="form-label">User ID</label>
                            <input type="text" class="form-control" id="user-id" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
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
    <script src="assets/js/chat.js"></script>
</body>
</html> 