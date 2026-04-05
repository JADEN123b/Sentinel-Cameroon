# Use official PHP with Apache image
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    # Build tools for compiling PHP extensions
    build-essential \
    # SQLite development libraries
    sqlite3 \
    libsqlite3-dev \
    # Additional utilities
    git \
    curl \
    wget \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    session \
    && docker-php-ext-enable pdo pdo_sqlite session

# Enable Apache modules needed for the application
RUN a2enmod rewrite headers

# Configure Apache
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' \
    /etc/apache2/sites-available/000-default.conf && \
    echo "<Directory /var/www/html>" >> /etc/apache2/sites-available/000-default.conf && \
    echo "    Options Indexes FollowSymLinks" >> /etc/apache2/sites-available/000-default.conf && \
    echo "    AllowOverride All" >> /etc/apache2/sites-available/000-default.conf && \
    echo "    Require all granted" >> /etc/apache2/sites-available/000-default.conf && \
    echo "</Directory>" >> /etc/apache2/sites-available/000-default.conf

# Set PHP configuration for production
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "session.save_path = /var/lib/php_sessions" >> /usr/local/etc/php/conf.d/docker-php.ini

# Create session directory and uploads directory with proper permissions
RUN mkdir -p /var/lib/php_sessions /var/www/html/uploads/incidents /var/www/html/uploads/profiles && \
    chown -R www-data:www-data /var/lib/php_sessions /var/www/html/uploads && \
    chmod 755 /var/lib/php_sessions /var/www/html/uploads

# Create a database directory with proper permissions
RUN mkdir -p /var/www/html/database/data && \
    chown -R www-data:www-data /var/www/html/database/data && \
    chmod 755 /var/www/html/database/data

# Copy application files
COPY . /var/www/html/

# Set proper permissions for the application
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Run entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Start Apache in foreground
CMD ["apache2-ctl", "start", "-D", "FOREGROUND"]
