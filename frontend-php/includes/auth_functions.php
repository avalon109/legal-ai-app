<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/session_handler.php';

/**
 * Validates a token from Authorization header
 * Returns an array with success status and user_id if valid
 */
function validateToken() {
    // Get authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    // Check if token exists
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return ['success' => false, 'message' => 'No token provided'];
    }
    
    $token = $matches[1];
    
    try {
        // Validate the token
        $sessionHandler = new AppSessionHandler();
        $user_id = $sessionHandler->validateSession($token);
        
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

/**
 * Gets user information by user ID
 * Returns user data or null if not found
 */
function getUserById($user_id) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT id, username, email, real_name, phone, status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() === 0) {
            return null;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user: " . $e->getMessage());
        return null;
    }
} 