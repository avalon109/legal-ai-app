<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/session_handler.php';

class AuthHandler {
    private $db;
    private $session;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->session = new AppSessionHandler();
    }
    
    public function register($username, $password, $email, $phone = null, $real_name = null) {
        try {
            // Validate input
            if (empty($username) || empty($password) || empty($email)) {
                throw new Exception("Required fields cannot be empty");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Check if username or email already exists
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username or email already exists");
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Generate UUID for user
            $user_id = uniqid('user_', true);

            // Insert new user
            $stmt = $conn->prepare(
                "INSERT INTO users (id, username, password_hash, email, phone, real_name, status) 
                 VALUES (?, ?, ?, ?, ?, ?, 'active')"
            );
            $stmt->execute([$user_id, $username, $password_hash, $email, $phone, $real_name]);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $user_id
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function login($username, $password) {
        try {
            $conn = $this->db->getConnection();
            // Get user - remove status check from query
            $stmt = $conn->prepare("SELECT id, password_hash, status FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Invalid credentials");
            }
            
            // Check password
            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception("Invalid credentials");
            }
            
            // Now check status if needed
            if (isset($user['status']) && $user['status'] === 'inactive') {
                throw new Exception("Account is inactive. Please contact support.");
            }

            // Update last login
            $stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Create session
            $session_token = $this->session->createSession($user['id']);

            return [
                'success' => true,
                'message' => 'Login successful',
                'token' => $session_token
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function logout($token) {
        try {
            $this->session->destroySession($token);
            return [
                'success' => true,
                'message' => 'Logout successful'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function validateSession($token) {
        try {
            $user_id = $this->session->validateSession($token);
            return [
                'success' => true,
                'user_id' => $user_id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 