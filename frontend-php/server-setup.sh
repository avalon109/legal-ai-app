#!/bin/bash

# Server-side setup script for TRA application
APP_PATH="/home/volantic/tra"
WEB_USER="www-data"
DB_NAME="tenant_rights"

echo "Setting up TRA application..."

# Create directory structure
echo "Creating directories..."
mkdir -p ${APP_PATH}/{config,includes,public,uploads,logs}

# Set proper permissions
echo "Setting permissions..."
chmod 755 ${APP_PATH}
chmod 755 ${APP_PATH}/{config,includes,public}
chmod 775 ${APP_PATH}/{uploads,logs}

# Set ownership
echo "Setting ownership..."
chown -R ${WEB_USER}:${WEB_USER} ${APP_PATH}

# Secure environment file if it exists
if [ -f "${APP_PATH}/config/env.php" ]; then
    echo "Securing environment file..."
    chmod 640 ${APP_PATH}/config/env.php
    chown root:${WEB_USER} ${APP_PATH}/config/env.php
fi

# Create database if it doesn't exist
echo "Setting up database..."
mysql -u tra@volanticsystems.nl -p <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
USE ${DB_NAME};
source ${APP_PATH}/config/schema.sql;
EOF

echo "Setup complete!"
echo "Please check the following:"
echo "1. Web server configuration (Apache/nginx)"
echo "2. Database connection in env.php"
echo "3. Log file permissions"
echo "4. Upload directory permissions" 