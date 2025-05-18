<?php
// Load configuration
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Always start session first
session_start();

// Debug info to include in responses
$debug = [];

require_once __DIR__ . '/../includes/profile_handler.php';
require_once __DIR__ . '/../includes/auth_handler.php';

$profile = new ProfileHandler();
$auth = new AuthHandler();

// Get token from various sources
$headers = getallheaders();
$session_token = null;
$token_source = null;

// Log all headers for debug
$all_headers = [];
foreach ($headers as $name => $value) {
    // Mask authorization value but keep prefix for debugging
    if (strtolower($name) === 'authorization' && strlen($value) > 15) {
        $masked_value = substr($value, 0, 15) . '...';
        $all_headers[$name] = $masked_value;
    } else {
        $all_headers[$name] = $value;
    }
}
$debug['headers'] = $all_headers;

// Check Authorization header (from JavaScript client)
if (isset($headers['Authorization'])) {
    $session_token = $headers['Authorization'];
    $token_source = 'Authorization header';
} 
// Apache may convert header to uppercase
else if (isset($headers['AUTHORIZATION'])) {
    $session_token = $headers['AUTHORIZATION'];
    $token_source = 'AUTHORIZATION header';
}
// Headers might be in $_SERVER with HTTP_ prefix
else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $session_token = $_SERVER['HTTP_AUTHORIZATION'];
    $token_source = 'HTTP_AUTHORIZATION server var';
}
// Check session (from PHP session)
else if (isset($_SESSION['session_token'])) {
    $session_token = $_SESSION['session_token'];
    $token_source = 'PHP session';
}
// Check query param as a fallback
else if (isset($_GET['token'])) {
    $session_token = $_GET['token'];
    $token_source = 'URL parameter';
}

$debug['token_source'] = $token_source;
$debug['token_found'] = !empty($session_token);

if (empty($session_token)) {
    echo json_encode([
        'success' => false, 
        'message' => 'No authorization token provided',
        'debug' => $debug
    ]);
    exit;
}

// If token has Bearer prefix, strip it
if (strpos($session_token, 'Bearer ') === 0) {
    $session_token = substr($session_token, 7);
    $debug['token_had_bearer'] = true;
}

$debug['token_length'] = strlen($session_token);
$debug['token_prefix'] = substr($session_token, 0, 10) . '...';

// Verify session token
$user = $auth->validateSession($session_token);
$debug['user_found'] = !empty($user);

if (!$user) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid or expired authorization token',
        'debug' => $debug
    ]);
    exit;
}

// Default action is to get profile - use GET for reads
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = 'get_profile';
} else {
    // Use POST data for writes
    $action = $_POST['action'] ?? 'get_profile';
}

// Process the action
try {
    switch ($action) {
        case 'get_profile':
            $response = ['success' => true];
            
            // Use try/catch for user profile to avoid fatal errors
            try {
                $user_profile = $profile->getProfile($user['user_id']);
                $response = array_merge($response, $user_profile);
            } catch (Exception $e) {
                $response['profile_error'] = $e->getMessage();
            }
            
            // Always include debug info
            $response['debug'] = $debug;
            
            // Single echo statement to prevent multiple outputs
            echo json_encode($response);
            break;
            
        case 'update_profile':
            $data = [
                'real_name' => $_POST['real_name'] ?? null,
                'email' => $_POST['email'] ?? null,
                'phone' => $_POST['phone'] ?? null
            ];
            $response = $profile->updateProfile($user['user_id'], $data);
            echo json_encode($response);
            break;
            
        case 'change_password':
            $response = $profile->changePassword(
                $user['user_id'],
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? ''
            );
            echo json_encode($response);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    // Global error handler
    echo json_encode([
        'success' => false,
        'message' => 'API error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
} 