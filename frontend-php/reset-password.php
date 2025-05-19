<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Legal AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Reset Password</h2>
                        
                        <!-- Request Reset Form -->
                        <form id="requestResetForm" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Request Password Reset</button>
                        </form>

                        <!-- Reset Password Form (Hidden by default) -->
                        <form id="resetPasswordForm" class="needs-validation d-none" novalidate>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword" required minlength="8">
                                <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" required>
                                <div class="invalid-feedback">Passwords do not match.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>

                        <!-- Alert for messages -->
                        <div id="alertMessage" class="alert d-none mt-3" role="alert"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const requestResetForm = document.getElementById('requestResetForm');
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            const alertMessage = document.getElementById('alertMessage');

            // Check if we have a reset token in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');

            if (token) {
                try {
                    // Validate the token
                    await auth.validateResetToken(token);
                    requestResetForm.classList.add('d-none');
                    resetPasswordForm.classList.remove('d-none');
                } catch (error) {
                    showAlert('Invalid or expired reset token. Please request a new one.', 'danger');
                    resetPasswordForm.classList.add('d-none');
                    requestResetForm.classList.remove('d-none');
                }
            }

            // Handle request reset form submission
            requestResetForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }

                try {
                    const email = document.getElementById('email').value;
                    await auth.requestPasswordReset(email);
                    showAlert('Password reset instructions have been sent to your email.', 'success');
                } catch (error) {
                    showAlert(error.message, 'danger');
                }
            });

            // Handle reset password form submission
            resetPasswordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }

                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (newPassword !== confirmPassword) {
                    document.getElementById('confirmPassword').setCustomValidity('Passwords do not match');
                    this.classList.add('was-validated');
                    return;
                }

                try {
                    await auth.resetPassword(token, newPassword);
                    showAlert('Password has been reset successfully. You can now login with your new password.', 'success');
                    setTimeout(() => {
                        window.location.href = '/login.php';
                    }, 3000);
                } catch (error) {
                    showAlert(error.message, 'danger');
                }
            });

            // Helper function to show alerts
            function showAlert(message, type) {
                alertMessage.textContent = message;
                alertMessage.className = `alert alert-${type} mt-3`;
            }

            // Password confirmation validation
            document.getElementById('confirmPassword').addEventListener('input', function() {
                const newPassword = document.getElementById('newPassword').value;
                if (this.value !== newPassword) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html> 