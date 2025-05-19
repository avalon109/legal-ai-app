<?php
// Simple API test endpoint
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API endpoint accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'],
    'request_path' => $_SERVER['REQUEST_URI']
]); 