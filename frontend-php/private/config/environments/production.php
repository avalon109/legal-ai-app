<?php
// Production-specific database configuration
define('DB_HOST', getenv('TRA_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('TRA_DB_USER') ?: 'tra@volanticsystems.nl');
define('DB_PASS', getenv('TRA_DB_PASS')); // Will be set via environment
define('DB_NAME', getenv('TRA_DB_NAME') ?: 'tenant_rights');

// Production-specific application configuration
define('SITE_URL', 'https://www.volantic.systems/tra');
define('DEBUG_MODE', false);

// Security settings for production
define('COOKIE_SECURE', true);
define('UPLOAD_DIR', '/home/volantic/tra/uploads/');

// Error reporting for production
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/volantic/tra/logs/error.log'); 