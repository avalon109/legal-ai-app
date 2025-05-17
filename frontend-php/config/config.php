<?php
// Load environment-specific configuration
$env = (strpos($_SERVER['HTTP_HOST'], 'volantic') !== false) ? 'production' : 'development';
$config = require_once __DIR__ . "/environments/{$env}.php";

// Database configuration
define('DB_HOST', $config['db_host']);
define('DB_NAME', $config['db_name']);
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_pass']);

// Application paths
define('ROOT_DIR', dirname(__DIR__));
define('INCLUDES_DIR', ROOT_DIR . '/includes');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('ASSETS_DIR', ROOT_DIR . '/assets');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', $config['display_errors']);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', $config['secure_cookies']); 