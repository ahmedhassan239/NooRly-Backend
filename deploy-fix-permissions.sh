#!/bin/bash

# Laravel Deployment Permission Fix Script
# Run this script on your server after deployment

echo "🔧 Fixing Laravel permissions..."

# Get the web server user (common: www-data, nginx, apache)
# Uncomment the one that matches your server:
WEB_USER="www-data"
# WEB_USER="nginx"
# WEB_USER="apache"

# Get the current directory
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "📁 Project directory: $PROJECT_DIR"
echo "👤 Web server user: $WEB_USER"

# Set ownership of storage and bootstrap/cache directories
echo "🔐 Setting ownership..."
sudo chown -R $WEB_USER:$WEB_USER $PROJECT_DIR/storage
sudo chown -R $WEB_USER:$WEB_USER $PROJECT_DIR/bootstrap/cache

# Set permissions
echo "📝 Setting permissions..."
sudo chmod -R 775 $PROJECT_DIR/storage
sudo chmod -R 775 $PROJECT_DIR/bootstrap/cache

# Ensure specific directories exist and are writable
echo "📂 Creating required directories..."
mkdir -p $PROJECT_DIR/storage/framework/sessions
mkdir -p $PROJECT_DIR/storage/framework/views
mkdir -p $PROJECT_DIR/storage/framework/cache/data
mkdir -p $PROJECT_DIR/storage/logs
mkdir -p $PROJECT_DIR/bootstrap/cache

# Set permissions again after creating directories
sudo chmod -R 775 $PROJECT_DIR/storage
sudo chmod -R 775 $PROJECT_DIR/bootstrap/cache

echo "✅ Permissions fixed!"
echo ""
echo "📋 Next steps:"
echo "1. Run: php artisan config:clear"
echo "2. Run: php artisan cache:clear"
echo "3. Run: php artisan route:clear"
echo "4. Run: php artisan view:clear"
echo "5. Run: php artisan storage:link"
echo "6. Run: php artisan optimize"
