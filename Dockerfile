FROM php:8.2-apache

# Install required PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    git \
    curl \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Configure Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create database directory with proper permissions
RUN mkdir -p /var/www/html/database && \
    chmod -R 755 /var/www/html/database && \
    chown -R www-data:www-data /var/www/html

# Enable .htaccess
RUN echo '<Directory /var/www/html>' > /etc/apache2/conf-available/override.conf && \
    echo '    AllowOverride All' >> /etc/apache2/conf-available/override.conf && \
    echo '</Directory>' >> /etc/apache2/conf-available/override.conf && \
    a2enconf override

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
