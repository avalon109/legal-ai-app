<?php
// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_handler.php';

// Debug logging
$debug_log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'get_params' => $_GET,
    'server_software' => $_SERVER['SERVER_SOFTWARE']
];

// Get request body
$input_data = file_get_contents('php://input');
$debug_log['raw_input'] = $input_data;

try {
    // Parse JSON input (but only for non-GET requests)
    $data = [];
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !empty($input_data)) {
        $data = json_decode($input_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $debug_log['json_error'] = json_last_error_msg();
            throw new Exception("JSON parsing error: " . json_last_error_msg());
        }
        $debug_log['parsed_data'] = $data;
    }
    
    // Get the HTTP method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $auth = new AuthHandler();
    $debug_log['auth_handler_initialized'] = true;

    switch ($method) {
        case 'POST':
            // Registration endpoint
            if (isset($_GET['action']) && $_GET['action'] === 'register') {
                $debug_log['action'] = 'register';
                
                // Validate required fields
                if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
                    $debug_log['error'] = 'Missing required fields';
                    throw new Exception("Missing required fields (username, password, email)");
                }
                
                $response = $auth->register(
                    $data['username'] ?? null,
                    $data['password'] ?? null,
                    $data['email'] ?? null,
                    $data['phone'] ?? null,
                    $data['real_name'] ?? null
                );
                $debug_log['auth_response'] = $response;
            }
            // Login endpoint
            else if (isset($_GET['action']) && $_GET['action'] === 'login') {
                $debug_log['action'] = 'login';
                
                // Validate required fields
                if (!isset($data['username']) || !isset($data['password'])) {
                    $debug_log['error'] = 'Missing required fields';
                    throw new Exception("Missing required fields (username, password)");
                }
                
                $response = $auth->login(
                    $data['username'] ?? null,
                    $data['password'] ?? null
                );
                $debug_log['auth_response'] = $response;
            }
            // Logout endpoint
            else if (isset($_GET['action']) && $_GET['action'] === 'logout') {
                $debug_log['action'] = 'logout';
                
                $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
                if (!$token) {
                    $debug_log['error'] = 'No authorization token';
                    throw new Exception('No authorization token provided');
                }
                
                $response = $auth->logout($token);
                $debug_log['auth_response'] = $response;
            }
            else {
                $debug_log['error'] = 'Invalid action';
                throw new Exception('Invalid action');
            }
            break;
            
        case 'GET':
            // Session validation endpoint
            if (isset($_GET['action']) && $_GET['action'] === 'validate') {
                $debug_log['action'] = 'validate';
                
                $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
                if (!$token) {
                    $debug_log['error'] = 'No authorization token';
                    throw new Exception('No authorization token provided');
                }
                
                $response = $auth->validateSession($token);
                $debug_log['auth_response'] = $response;
            }
            // Username availability check
            else if (isset($_GET['action']) && $_GET['action'] === 'check_username' && isset($_GET['username'])) {
                $debug_log['action'] = 'check_username';
                
                // Get the username from the query
                $username = $_GET['username'];
                
                // Create database connection with better error handling
                try {
                    require_once __DIR__ . '/../includes/Database.php';
                    $debug_log['db_include'] = 'Database.php included';
                    
                    // Use getInstance instead of direct constructor
                    try {
                        $db = Database::getInstance();
                        $connection = $db->getConnection();
                        $debug_log['db_instance'] = 'Database successfully instantiated';
                        
                        // Use direct PDO query which should work on all environments
                        $stmt = $connection->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        
                        $response = [
                            'success' => true,
                            'available' => ($stmt->rowCount() === 0),
                            'exists' => ($stmt->rowCount() > 0)
                        ];
                        
                    } catch (Exception $e) {
                        $debug_log['db_instance_error'] = $e->getMessage();
                        throw new Exception("Database instantiation error: " . $e->getMessage());
                    }
                } catch (Exception $e) {
                    $debug_log['db_error'] = $e->getMessage();
                    // Return a specific response rather than throwing an exception
                    $response = [
                        'success' => false,
                        'message' => "Database error: " . $e->getMessage(),
                        'error_type' => 'database'
                    ];
                }
                
                $debug_log['auth_response'] = $response;
            }
            else {
                $debug_log['error'] = 'Invalid action';
                throw new Exception('Invalid action');
            }
            break;
            
        default:
            $debug_log['error'] = 'Method not allowed';
            throw new Exception('Method not allowed');
    }
    
    // Add debug info in development mode
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        $response['_debug'] = $debug_log;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // Include debug info on errors
    $error_response['_debug'] = $debug_log;
    $error_response['_debug']['exception'] = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    echo json_encode($error_response);
} 