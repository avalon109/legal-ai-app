<?php
return [
    // Database settings for local development
    'db_host' => 'localhost',
    'db_name' => 'tenant_rights',
    'db_user' => 'root',
    'db_pass' => '',  // Default XAMPP password is empty
    
    // Development environment settings
    'display_errors' => true,
    'secure_cookies' => false,  // False for HTTP in development
    
    // Other development-specific settings
    'debug_mode' => true,
    'log_level' => 'debug',
    'api_debug' => true
]; 