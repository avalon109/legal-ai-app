<?php
// Load configuration
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/password_reset_handler.php';

$method = $_SERVER['REQUEST_METHOD'];
$reset = new PasswordResetHandler();

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Request password reset
            if (!isset($_GET['action'])) {
                $response = $reset->requestReset($data['email'] ?? null);
            }
            // Reset password with token
            else if ($_GET['action'] === 'reset') {
                $response = $reset->resetPassword(
                    $data['token'] ?? null,
                    $data['new_password'] ?? null
                );
            }
            else {
                throw new Exception('Invalid action');
            }
            break;
            
        case 'GET':
            // Validate reset token
            if (isset($_GET['token'])) {
                $response = $reset->validateToken($_GET['token']);
            }
            else {
                throw new Exception('Token not provided');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 