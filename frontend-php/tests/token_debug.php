<?php
// Set headers for proper response
header('Content-Type: text/html');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include required files
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth_handler.php';

// Storage for debug output
$debug = [];

// Session info
$debug['session'] = [
    'id' => session_id(),
    'session_data' => $_SESSION
];

// Request info
$debug['request'] = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'php_version' => PHP_VERSION,
];

// Get headers
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (substr($key, 0, 5) === 'HTTP_') {
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
}
$debug['headers'] = $headers;

// Try multiple methods for getting headers
$debug['getallheaders'] = function_exists('getallheaders') ? getallheaders() : 'Function not available';

// Debug GET parameters
$debug['get_params'] = $_GET;

// Token detection
$token = null;
$token_source = 'Not found';

// First check query parameter (highest priority)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $token_source = 'GET parameter';
}
// Check Authorization header
else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = $_SERVER['HTTP_AUTHORIZATION'];
    $token_source = 'HTTP_AUTHORIZATION server var';
} 
// Check headers from getallheaders
else if (function_exists('getallheaders')) {
    $allheaders = getallheaders();
    if (isset($allheaders['Authorization'])) {
        $token = $allheaders['Authorization'];
        $token_source = 'getallheaders() Authorization';
    }
}
// Check for specific headers
else if (isset($_SERVER['Authorization'])) {
    $token = $_SERVER['Authorization'];
    $token_source = 'Authorization server var';
}
// Check session
else if (isset($_SESSION['session_token'])) {
    $token = $_SESSION['session_token'];
    $token_source = 'PHP Session session_token';
}

$debug['token'] = [
    'source' => $token_source,
    'found' => !empty($token),
    'value' => !empty($token) ? (strlen($token) > 20 ? substr($token, 0, 20) . '...' : $token) : 'Not found',
    'length' => !empty($token) ? strlen($token) : 0,
    'bearer_prefix' => !empty($token) && strpos($token, 'Bearer ') === 0 ? 'Yes' : 'No'
];

// If token has Bearer prefix, also check the extracted token
if (!empty($token) && strpos($token, 'Bearer ') === 0) {
    $extracted_token = substr($token, 7);
    $debug['extracted_token'] = [
        'value' => strlen($extracted_token) > 20 ? substr($extracted_token, 0, 20) . '...' : $extracted_token,
        'length' => strlen($extracted_token)
    ];
}

// Database connection test
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $debug['database'] = [
        'connected' => true
    ];
    
    // Test token in database
    if (!empty($token)) {
        // If token has Bearer prefix, extract it
        $search_token = $token;
        if (strpos($token, 'Bearer ') === 0) {
            $search_token = substr($token, 7);
        }
        
        // Try session_token column
        $stmt = $conn->prepare("SELECT * FROM user_sessions WHERE token = ?");
        $stmt->execute([$search_token]);
        $debug['db_validation']['session_token_column'] = [
            'found' => $stmt->rowCount() > 0,
            'count' => $stmt->rowCount()
        ];
        
        // Try token column
        $stmt = $conn->prepare("SELECT * FROM user_sessions WHERE token = ?");
        $stmt->execute([$search_token]);
        $debug['db_validation']['token_column'] = [
            'found' => $stmt->rowCount() > 0,
            'count' => $stmt->rowCount()
        ];
        
        // Show schema of user_sessions table
        $stmt = $conn->prepare("DESCRIBE user_sessions");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug['db_schema']['user_sessions'] = $columns;
        
        // Count all sessions
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_sessions");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug['db_stats']['session_count'] = $count['count'];
        
        // Get all field names for a sample session
        $stmt = $conn->prepare("SELECT * FROM user_sessions LIMIT 1");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            $debug['db_sample']['fields'] = array_keys($sample);
        }
        
        // Try auth handler
        $auth = new AuthHandler();
        $user = $auth->validateSession($token);
        $debug['auth_handler'] = [
            'valid_session' => !empty($user),
            'user_data' => !empty($user) ? 'User found' : 'No user found'
        ];
    }
} catch (Exception $e) {
    $debug['database'] = [
        'connected' => false,
        'error' => $e->getMessage()
    ];
}

// Output debug information as HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Debugging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Authentication Token Debugging</h1>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                Token Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Source:</strong> <?php echo $debug['token']['source']; ?></p>
                        <p><strong>Found:</strong> <?php echo $debug['token']['found'] ? 'Yes' : 'No'; ?></p>
                        <p><strong>Value:</strong> <?php echo $debug['token']['value']; ?></p>
                        <p><strong>Length:</strong> <?php echo $debug['token']['length']; ?></p>
                        <p><strong>Has Bearer Prefix:</strong> <?php echo $debug['token']['bearer_prefix']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <?php if (isset($debug['extracted_token'])): ?>
                        <h5>Extracted Token (without Bearer)</h5>
                        <p><strong>Value:</strong> <?php echo $debug['extracted_token']['value']; ?></p>
                        <p><strong>Length:</strong> <?php echo $debug['extracted_token']['length']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                Database Validation
            </div>
            <div class="card-body">
                <?php if (isset($debug['db_validation'])): ?>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Token Check in session_token column</h5>
                        <p><strong>Found:</strong> <?php echo $debug['db_validation']['session_token_column']['found'] ? 'Yes' : 'No'; ?></p>
                        <p><strong>Count:</strong> <?php echo $debug['db_validation']['session_token_column']['count']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Token Check in token column</h5>
                        <p><strong>Found:</strong> <?php echo $debug['db_validation']['token_column']['found'] ? 'Yes' : 'No'; ?></p>
                        <p><strong>Count:</strong> <?php echo $debug['db_validation']['token_column']['count']; ?></p>
                    </div>
                </div>
                <?php else: ?>
                <p>No database validation performed - token not found</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-success text-white">
                Session Information
            </div>
            <div class="card-body">
                <p><strong>Session ID:</strong> <?php echo $debug['session']['id']; ?></p>
                <h5>Session Data:</h5>
                <pre><?php echo print_r($debug['session']['session_data'], true); ?></pre>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-warning text-dark">
                Headers
            </div>
            <div class="card-body">
                <h5>getallheaders():</h5>
                <pre><?php echo is_array($debug['getallheaders']) ? print_r($debug['getallheaders'], true) : $debug['getallheaders']; ?></pre>
                
                <h5>HTTP Headers from $_SERVER:</h5>
                <pre><?php echo print_r($debug['headers'], true); ?></pre>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-secondary text-white">
                Authentication Handler Results
            </div>
            <div class="card-body">
                <?php if (isset($debug['auth_handler'])): ?>
                <p><strong>Valid Session:</strong> <?php echo $debug['auth_handler']['valid_session'] ? 'Yes' : 'No'; ?></p>
                <p><strong>User Data:</strong> <?php echo $debug['auth_handler']['user_data']; ?></p>
                <?php else: ?>
                <p>Authentication handler not tested</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-dark text-white">
                Database Schema
            </div>
            <div class="card-body">
                <?php if (isset($debug['db_schema'])): ?>
                <h5>user_sessions Table Structure:</h5>
                <pre><?php echo print_r($debug['db_schema']['user_sessions'], true); ?></pre>
                
                <h5>Session Count: <?php echo $debug['db_stats']['session_count']; ?></h5>
                
                <?php if (isset($debug['db_sample'])): ?>
                <h5>Available Fields in user_sessions:</h5>
                <pre><?php echo print_r($debug['db_sample']['fields'], true); ?></pre>
                <?php endif; ?>
                
                <?php else: ?>
                <p>No database schema information available</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-danger text-white">
                Server Environment
            </div>
            <div class="card-body">
                <p><strong>Request Method:</strong> <?php echo $debug['request']['method']; ?></p>
                <p><strong>Request URI:</strong> <?php echo htmlspecialchars($debug['request']['uri']); ?></p>
                <p><strong>Server Software:</strong> <?php echo htmlspecialchars($debug['request']['server_software']); ?></p>
                <p><strong>PHP Version:</strong> <?php echo htmlspecialchars($debug['request']['php_version']); ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-light">
                Token Test Form
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="mb-3">
                        <label for="token" class="form-label">Test Token</label>
                        <input type="text" class="form-control" id="token" name="token" 
                               placeholder="Enter token to test">
                    </div>
                    <button type="submit" class="btn btn-primary">Test Token</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 