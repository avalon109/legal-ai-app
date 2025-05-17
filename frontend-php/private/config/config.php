<?php
// Load environment configuration
$envFile = __DIR__ . '/env.php';
if (file_exists($envFile)) {
    require_once $envFile;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // XAMPP default
define('DB_PASS', '');         // XAMPP default blank password
define('DB_NAME', 'tenant_rights');

// Application configuration
define('SITE_URL', 'http://localhost/tra');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// GDPR and Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('COOKIE_SECURE', false);   // false for local development
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Strict');

// Mock AI API Configuration (for MVP)
define('AI_ENDPOINT', 'mock_ai.php');
define('AI_MAX_TOKENS', 2048);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); 