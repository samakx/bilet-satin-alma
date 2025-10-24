#!/bin/bash

# Create database directory if it doesn't exist
mkdir -p /var/www/html/database

# Set proper permissions for database directory
chmod -R 755 /var/www/html/database
chown -R www-data:www-data /var/www/html/database

# Initialize database if it doesn't exist
if [ ! -f /var/www/html/database/tickets.db ]; then
    echo "Initializing database..."
    # Database will be created by the application on first run
fi

# Set proper permissions for the application
chown -R www-data:www-data /var/www/html

# Start Apache
exec apache2-foreground
