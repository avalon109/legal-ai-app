<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in JSON output

try {
    // Test database connection
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Try a simple query to verify full functionality
    $stmt = $connection->query('SELECT NOW() as time');
    $result = $stmt->fetch();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'details' => [
            'server_time' => $result['time'],
            'php_version' => PHP_VERSION,
            'mysql_version' => $connection->getAttribute(PDO::ATTR_SERVER_VERSION)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
}
// Ensure no additional output
exit(); 