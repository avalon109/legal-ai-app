<?php
// This is a debug endpoint to help troubleshoot authentication issues
header('Content-Type: application/json');

// Get all request headers
$headers = getallheaders();
$server_data = $_SERVER;

// Sanitize sensitive data in headers (e.g., cookies)
if (isset($headers['Cookie'])) {
    $headers['Cookie'] = '[REDACTED FOR PRIVACY]';
}

if (isset($server_data['HTTP_COOKIE'])) {
    $server_data['HTTP_COOKIE'] = '[REDACTED FOR PRIVACY]';
}

// Extract auth token
$auth_header = $headers['Authorization'] ?? null;
$auth_server = $server_data['HTTP_AUTHORIZATION'] ?? null;

// Process token
$token = null;
$token_prefix = null;

if ($auth_header) {
    if (strpos($auth_header, 'Bearer ') === 0) {
        $token = substr($auth_header, 7);
        $token_prefix = 'Bearer';
    } else {
        $token = $auth_header;
        $token_prefix = 'None (Direct)';
    }
}

// Prepare response data
$response = [
    'success' => true,
    'message' => 'Debug information',
    'time' => date('Y-m-d H:i:s'),
    'auth' => [
        'auth_header_exists' => isset($headers['Authorization']),
        'auth_server_exists' => isset($server_data['HTTP_AUTHORIZATION']),
        'token_value' => $token ? substr($token, 0, 10) . '...' : null,
        'token_prefix' => $token_prefix,
        'token_length' => $token ? strlen($token) : 0
    ],
    'headers' => $headers,
    'server' => array_filter($server_data, function($key) {
        return in_array($key, [
            'REQUEST_METHOD',
            'HTTP_HOST',
            'HTTP_ACCEPT',
            'HTTP_USER_AGENT',
            'SCRIPT_NAME',
            'REQUEST_URI',
            'QUERY_STRING',
            'HTTP_REFERER'
        ]);
    }, ARRAY_FILTER_USE_KEY)
];

echo json_encode($response, JSON_PRETTY_PRINT); 