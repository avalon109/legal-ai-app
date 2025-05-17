<?php
class Environment {
    private static $env = null;
    private static $config = [];

    public static function init() {
        // Default to 'development' if no environment is set
        self::$env = getenv('APP_ENV') ?: 'development';
        
        // Load base config
        require_once __DIR__ . '/config.php';
        
        // Load environment-specific config if exists
        $envFile = __DIR__ . '/environments/' . self::$env . '.php';
        if (file_exists($envFile)) {
            require_once $envFile;
        }
    }

    public static function getEnvironment() {
        return self::$env;
    }

    public static function isDevelopment() {
        return self::$env === 'development';
    }

    public static function isProduction() {
        return self::$env === 'production';
    }
} 