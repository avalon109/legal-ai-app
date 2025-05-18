<?php
// Simple diagnostic script to test username availability check
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => (strpos($_SERVER['HTTP_HOST'], 'volantic') !== false) ? 'production' : 'development',
    'php_version' => PHP_VERSION,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'get_params' => $_GET
];

// Get username from query string
$username = $_GET['username'] ?? 'test_user';

// Test database connection
try {
    // Try instantiating database with singleton pattern
    $debug_info['db_test_1'] = [
        'method' => 'Singleton getInstance',
        'status' => 'attempting'
    ];
    
    $db = Database::getInstance();
    $debug_info['db_test_1']['status'] = 'success';
    $debug_info['db_test_1']['available_methods'] = get_class_methods($db);
    
    // Get the connection
    $conn = $db->getConnection();
    $debug_info['connection'] = [
        'status' => 'success',
        'pdo_methods' => get_class_methods($conn)
    ];
    
    // Test standard PDO query
    $debug_info['pdo_query'] = [
        'status' => 'attempting'
    ];
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->rowCount();
    
    $debug_info['pdo_query']['status'] = 'success';
    $debug_info['pdo_query']['result'] = [
        'rows' => $result,
        'username_exists' => ($result > 0)
    ];
    
    // Test query method if available
    if (method_exists($db, 'query')) {
        $debug_info['query_method'] = [
            'exists' => true,
            'status' => 'attempting'
        ];
        
        $stmt = $db->query("SELECT id FROM users WHERE username = ?", [$username]);
        $result = $stmt->rowCount();
        
        $debug_info['query_method']['status'] = 'success';
        $debug_info['query_method']['result'] = [
            'rows' => $result,
            'username_exists' => ($result > 0)
        ];
    } else {
        $debug_info['query_method'] = [
            'exists' => false
        ];
    }
    
    // Add server time info
    $timeStmt = $conn->query('SELECT NOW() as time');
    $time = $timeStmt->fetch()['time'];
    $debug_info['server_info'] = [
        'time' => $time,
        'php_time' => date('Y-m-d H:i:s')
    ];
    
    // Add final result
    $debug_info['final_result'] = [
        'success' => true,
        'message' => 'All tests completed',
        'username' => $username,
        'username_exists' => $debug_info['pdo_query']['result']['username_exists'] ?? null
    ];
    
} catch (Exception $e) {
    $debug_info['error'] = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    $debug_info['final_result'] = [
        'success' => false,
        'message' => 'Database test failed: ' . $e->getMessage()
    ];
}

// Output debug info
echo json_encode($debug_info, JSON_PRETTY_PRINT); 