#!/bin/bash
set -e

# Database configuration
DB_PATH="${DB_PATH:-/var/www/html/database/data/sentinel_cameroon.sqlite}"
DB_DIR=$(dirname "$DB_PATH")

# Ensure database directory exists with proper permissions
mkdir -p "$DB_DIR"
chown www-data:www-data "$DB_DIR"
chmod 755 "$DB_DIR"

# Initialize database if it doesn't exist
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database at $DB_PATH..."
    
    # Create the database file
    touch "$DB_PATH"
    chown www-data:www-data "$DB_PATH"
    chmod 644 "$DB_PATH"
    
    # Initialize database schema using PHP script
    php /var/www/html/database/init-sqlite.php "$DB_PATH"
    
    echo "Database initialized successfully"
else
    echo "Database already exists at $DB_PATH"
fi

# Verify database permissions
chown www-data:www-data "$DB_PATH"
chmod 644 "$DB_PATH"

# Verify uploads directory permissions
chown -R www-data:www-data /var/www/html/uploads
chmod -R 755 /var/www/html/uploads

echo "Container setup complete, starting Apache..."

# Execute the main command
exec "$@"
