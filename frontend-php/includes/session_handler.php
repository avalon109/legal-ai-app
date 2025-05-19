<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class AppSessionHandler {
    private $db;
    private const SESSION_LIFETIME = 86400; // 24 hours in seconds

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createSession($user_id) {
        try {
            // Generate a secure random token
            $token = bin2hex(random_bytes(32));
            
            // Calculate expiration
            $expires_at = date('Y-m-d H:i:s', time() + self::SESSION_LIFETIME);
            
            // Insert session
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare(
                "INSERT INTO user_sessions (id, user_id, token, expires_at) 
                 VALUES (?, ?, ?, ?)"
            );
            $session_id = uniqid('sess_', true);
            $stmt->execute([$session_id, $user_id, $token, $expires_at]);
            
            return $token;
        } catch (Exception $e) {
            throw new Exception("Failed to create session: " . $e->getMessage());
        }
    }

    public function validateSession($token) {
        try {
            // Clean expired sessions
            $this->cleanExpiredSessions();
            
            // Get valid session
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare(
                "SELECT user_id FROM user_sessions 
                 WHERE token = ? AND expires_at > CURRENT_TIMESTAMP"
            );
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Invalid or expired session");
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['user_id'];
        } catch (Exception $e) {
            throw new Exception("Session validation failed: " . $e->getMessage());
        }
    }

    public function destroySession($token) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE token = ?");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Session not found");
            }
        } catch (Exception $e) {
            throw new Exception("Failed to destroy session: " . $e->getMessage());
        }
    }

    private function cleanExpiredSessions() {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE expires_at <= CURRENT_TIMESTAMP");
            $stmt->execute();
        } catch (Exception $e) {
            // Log error but don't throw - this is a maintenance operation
            error_log("Failed to clean expired sessions: " . $e->getMessage());
        }
    }
} 