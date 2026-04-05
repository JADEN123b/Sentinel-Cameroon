# Use the official PHP Apache image
FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files to the container
COPY . /var/www/html/

# Ensure permissions are correct
RUN chown -R www-data:www-data /var/www/html

# Update Apache configuration to allow .htaccess and set document root if needed
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port (Render uses PORT env var, but Apache defaults to 80)
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
