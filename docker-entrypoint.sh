#!/bin/bash
set -e

# Update PHP configuration with environment variables
cat > /usr/local/etc/php/conf.d/custom.ini <<EOF
upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE:-50M}
post_max_size = ${PHP_POST_MAX_SIZE:-50M}
memory_limit = ${PHP_MEMORY_LIMIT:-256M}
max_execution_time = ${PHP_MAX_EXECUTION_TIME:-300}
display_errors = ${PHP_DISPLAY_ERRORS:-Off}
log_errors = On
error_log = /var/log/apache2/php_errors.log
EOF

# Ensure uploads directory exists and has correct permissions
UPLOAD_DIR_PATH="/var/www/html/${UPLOAD_DIR:-uploads}"
mkdir -p "$UPLOAD_DIR_PATH"
chown -R www-data:www-data "$UPLOAD_DIR_PATH"
chmod -R 777 "$UPLOAD_DIR_PATH"

# Set proper permissions for the web directory
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Ensure uploads directory is writable
chmod -R 777 "$UPLOAD_DIR_PATH"

echo "=========================================="
echo "Dialektika Portal Berita - Starting..."
echo "=========================================="
echo "Environment: ${APP_ENV:-development}"
echo "PHP Version: $(php -v | head -n 1)"
echo "Database Host: ${DB_HOST:-not set}"
echo "Upload Directory: ${UPLOAD_DIR:-uploads}"
echo "Upload Max Filesize: ${PHP_UPLOAD_MAX_FILESIZE:-50M}"
echo "=========================================="

# Execute the main command (apache2-foreground)
exec "$@"
