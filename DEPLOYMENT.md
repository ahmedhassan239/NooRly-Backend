# Deployment Guide

## Quick Fix for 403 Forbidden Error

After deploying to your server, run these commands to fix permissions:

### Option 1: Using the Script (Recommended)

```bash
chmod +x deploy-fix-permissions.sh
sudo ./deploy-fix-permissions.sh
```

### Option 2: Manual Commands

```bash
# Replace 'www-data' with your web server user (nginx, apache, etc.)
# Find your web server user: ps aux | grep -E 'nginx|apache|httpd'

# Set ownership
sudo chown -R www-data:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 775 storage bootstrap/cache

# Create required directories
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Create storage link
php artisan storage:link

# Optimize for production
php artisan optimize
```

## Complete Deployment Checklist

### 1. Upload Files
```bash
# Upload your project files to the server
# Ensure .env_server is copied to .env
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 3. Configure Environment
```bash
# Copy .env_server to .env
cp .env_server .env

# Generate application key (if needed)
php artisan key:generate

# Update APP_URL in .env to match your domain
# APP_URL=https://admin.theqaf.org
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed
```

### 5. Fix Permissions
```bash
# Run the permission fix script
sudo ./deploy-fix-permissions.sh
```

### 6. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### 7. Create Storage Link
```bash
php artisan storage:link
```

### 8. Create Admin User
```bash
php artisan make:filament-user
```

### 9. Verify Web Server Configuration

#### For Nginx:
```nginx
server {
    listen 80;
    server_name admin.theqaf.org;
    root /path/to/your/project/Backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### For Apache:
```apache
<VirtualHost *:80>
    ServerName admin.theqaf.org
    DocumentRoot /path/to/your/project/Backend/public

    <Directory /path/to/your/project/Backend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Troubleshooting

### 403 Forbidden After Login
- Check storage and bootstrap/cache permissions
- Ensure web server user owns these directories
- Check SELinux (if on CentOS/RHEL): `sudo setsebool -P httpd_can_network_connect 1`

### Database Connection Errors
- Verify .env file has correct database credentials
- Check MySQL user has proper permissions
- Ensure MySQL is running: `sudo systemctl status mysql`

### Assets Not Loading
- Run `npm run build`
- Check `public/build` directory exists
- Verify web server can serve static files

### Session Issues
- Check storage/framework/sessions is writable
- Verify SESSION_DRIVER in .env
- Clear session cache: `php artisan session:clear`
