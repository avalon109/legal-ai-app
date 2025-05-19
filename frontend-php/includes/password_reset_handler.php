<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/mail.php';

class PasswordResetHandler {
    private $db;
    private $mailer;
    private const TOKEN_LIFETIME = 3600; // 1 hour in seconds

    public function __construct() {
        $this->db = Database::getInstance();
        $this->mailer = new Mailer();
    }

    public function requestReset($email) {
        try {
            $conn = $this->db->getConnection();
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Get user by email
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Email not found");
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + self::TOKEN_LIFETIME);
            
            // Save reset token
            $stmt = $conn->prepare(
                "INSERT INTO password_resets (id, user_id, token, expires_at) 
                 VALUES (?, ?, ?, ?)"
            );
            $reset_id = uniqid('reset_', true);
            $stmt->execute([$reset_id, $user['id'], $token, $expires_at]);

            // Send reset email
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
            $email_body = "
                Hello {$user['username']},
                
                You have requested to reset your password. Please click the link below to proceed:
                
                {$reset_link}
                
                This link will expire in 1 hour.
                
                If you did not request this reset, please ignore this email.
                
                Best regards,
                Legal AI Team
            ";

            $this->mailer->send(
                $email,
                "Password Reset Request",
                $email_body
            );

            return [
                'success' => true,
                'message' => 'Password reset instructions sent to your email'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function validateToken($token) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare(
                "SELECT r.id, r.user_id, u.username 
                 FROM password_resets r
                 JOIN users u ON u.id = r.user_id
                 WHERE r.token = ? 
                 AND r.expires_at > CURRENT_TIMESTAMP
                 AND r.used = 0"
            );
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Invalid or expired reset token");
            }

            return [
                'success' => true,
                'data' => $stmt->fetch(PDO::FETCH_ASSOC)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function resetPassword($token, $new_password) {
        try {
            $conn = $this->db->getConnection();
            // Validate token
            $validation = $this->validateToken($token);
            if (!$validation['success']) {
                throw new Exception($validation['message']);
            }

            $reset_data = $validation['data'];

            // Update password
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $reset_data['user_id']]);

            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);

            return [
                'success' => true,
                'message' => 'Password has been reset successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?> 