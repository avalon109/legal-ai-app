<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class ProfileHandler {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getProfile($user_id) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare(
                "SELECT id, username, email, real_name, phone, created_at, last_login 
                 FROM users 
                 WHERE id = ? AND status = 'active'"
            );
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("User not found");
            }
            
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            unset($profile['password_hash']); // Ensure password hash is never sent
            
            return [
                'success' => true,
                'user' => $profile
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function updateProfile($user_id, $data) {
        try {
            $conn = $this->db->getConnection();
            // Validate email if it's being updated
            if (isset($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format");
                }
                
                // Check if email is already taken by another user
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $user_id]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception("Email is already in use");
                }
            }
            
            // Build update query dynamically based on provided fields
            $updateFields = [];
            $params = [];
            $allowedFields = ['email', 'real_name', 'phone'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception("No valid fields to update");
            }
            
            // Add user_id to params
            $params[] = $user_id;
            
            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("No changes made to profile");
            }
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            $conn = $this->db->getConnection();
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password_hash'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Update to new password
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 