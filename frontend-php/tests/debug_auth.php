<?php
// Set headers to allow for debugging
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get all headers
$headers = getallheaders();
$debug_info = [];

// Session info
session_start();
$debug_info['session'] = [
    'id' => session_id(),
    'data' => $_SESSION
];

// Request info
$debug_info['request'] = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_SERVER['REQUEST_URI'],
    'headers' => $headers
];

// Authorization header handling
$debug_info['auth'] = [
    'auth_header_exists' => isset($headers['Authorization']),
    'auth_header_value' => isset($headers['Authorization']) ? substr($headers['Authorization'], 0, 10) . '...' : 'not set'
];

// Get token from various sources
$token = null;

// Check Authorization header
if (isset($headers['Authorization'])) {
    $token = $headers['Authorization'];
    $debug_info['token_source'] = 'Authorization header';
}
// Check query param
else if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $debug_info['token_source'] = 'query parameter';
}
// Check session
else if (isset($_SESSION['session_token'])) {
    $token = $_SESSION['session_token'];
    $debug_info['token_source'] = 'session';
}
else {
    $debug_info['token_source'] = 'none';
}

$debug_info['token'] = $token ? substr($token, 0, 10) . '...' : null;
$debug_info['token_length'] = $token ? strlen($token) : 0;

// Check database connection
require_once '../config/config.php';
require_once '../includes/Database.php';
try {
    // Use getInstance() instead of direct constructor since it's private
    $db = Database::getInstance();
    $debug_info['database'] = [
        'connection' => 'success'
    ];
    
    // If we have a token, check if it exists in database
    if ($token) {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM user_sessions WHERE token = ?");
        $stmt->execute([$token]);
        $debug_info['token_validation'] = [
            'exists_in_db' => $stmt->rowCount() > 0
        ];
        
        if ($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            $session_safe = [
                'id' => $session['id'] ?? 'unknown',
                'user_id' => $session['user_id'] ?? 'unknown',
                'expires_at' => $session['expires_at'] ?? 'unknown'
            ];
            $debug_info['token_validation']['session_data'] = $session_safe;
        }
    }
} catch (Exception $e) {
    $debug_info['database'] = [
        'connection' => 'failed',
        'error' => $e->getMessage()
    ];
}

// Output debug info
echo json_encode($debug_info, JSON_PRETTY_PRINT);
?> 