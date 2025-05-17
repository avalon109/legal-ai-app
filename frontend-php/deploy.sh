#!/bin/bash

# Set environment variables
echo "SetEnv APP_ENV production" > /home/volantic/tra/.htaccess

# Set proper permissions
chown -R volantic:volantic /home/volantic/tra
find /home/volantic/tra -type d -exec chmod 755 {} \;
find /home/volantic/tra -type f -exec chmod 644 {} \;
chmod 777 /home/volantic/tra/uploads
chmod 777 /home/volantic/logs/tra

# Append security rules to .htaccess
cat >> /home/volantic/tra/.htaccess << 'EOL'
Options -Indexes

# Allow direct access to public files
<FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico)$">
    Order allow,deny
    Allow from all
</FilesMatch>

# Protect sensitive directories
<FilesMatch "^(config|includes)/">
    Order allow,deny
    Deny from all
</FilesMatch>

# Enable PHP error handling
php_flag display_errors off
php_value error_reporting E_ALL
php_flag log_errors on
php_value error_log /home/volantic/logs/tra/php_errors.log
EOL

# Create logs directory if it doesn't exist
mkdir -p /home/volantic/logs/tra
chmod 777 /home/volantic/logs/tra

# Test PHP configuration
php -r "echo 'PHP is working';" > /home/volantic/tra/test.php

echo "Server configuration complete. Please check http://yourdomain.com/tra/test.php" 