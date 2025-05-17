<?php
// Development-specific database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tenant_rights');

// Development-specific application configuration
define('SITE_URL', 'http://localhost:8080/tra');
define('DEBUG_MODE', true);

// Security settings for development
define('COOKIE_SECURE', false);

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1); 