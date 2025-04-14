# Use the official PHP 8.2 CLI image as a base
FROM php:8.2-fpm AS prebuild

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    nano \
    procps \
    unzip \
    supervisor \
    cron \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    nginx \
    logrotate \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip pdo pdo_mysql intl calendar gd exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Increase PHP memory limit and other PHP configurations
RUN { \
    echo "memory_limit=512M"; \
    echo "upload_max_filesize=100M"; \
    echo "post_max_size=120M"; \
    echo "max_execution_time=300"; \
    echo "max_input_time=300"; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Copy nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN rm -rf /etc/nginx/sites-enabled/default

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add Composer's global bin directory to the system PATH
ENV PATH=/root/.composer/vendor/bin:$PATH


# PHP Application build stage - Now before Node.js
FROM prebuild AS builder

# Copy application files to PHP stage
COPY . /var/www

# Define ARGs for environment variables
ARG APP_ENV=stage

# Copy environment file based on build arg
COPY .env.$APP_ENV .env

RUN composer install --no-dev --optimize-autoloader

# Node.js build stage for frontend assets - MOVED AFTER composer install
FROM node:23.11.0 AS node-builder

# Set working directory
WORKDIR /var/www

# Copy the application code including composer dependencies
COPY --from=builder /var/www /var/www

# Install npm dependencies
RUN npm ci

# Build the assets (which now has access to Ziggy)
RUN npm run build

# Create a final combined stage
FROM builder AS combined

# Copy built assets from node stage
COPY --from=node-builder /var/www/public/build /var/www/public/build
COPY --from=node-builder /var/www/bootstrap/ssr /var/www/bootstrap/ssr
COPY --from=node-builder /var/www/node_modules /var/www/node_modules

# Create a separate stage for testing
FROM combined AS testing
RUN php artisan config:clear && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Final stage for production/staging
FROM combined AS web

# Ensure permissions for the Laravel storage and cache directories
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

RUN apt-get update && apt-get install -y curl gnupg && \
    curl -fsSL https://deb.nodesource.com/setup_23.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
RUN npm prune --omit=dev

# Generate application key
RUN php artisan key:generate

# Create supervisord.conf
RUN { \
    echo "[supervisord]"; \
    echo "nodaemon=true"; \
    echo ""; \
    echo "[program:php-fpm]"; \
    echo "command=php-fpm"; \
    echo ""; \
    echo "[program:nginx]"; \
    echo "command=nginx -g 'daemon off;'"; \
    echo "[program:ssr]"; \
    echo "command=node bootstrap/ssr/ssr.mjs"; \
    echo "directory=/var/www"; \
    echo "autostart=true"; \
    echo "autorestart=true"; \
    echo "stdout_logfile=/dev/stdout"; \
    echo "stderr_logfile=/dev/stderr"; \
    } > /etc/supervisor/conf.d/supervisord.conf

# Configure logrotate for Laravel and Nginx logs
RUN { \
    echo "/var/www/storage/logs/*.log {"; \
    echo "    daily"; \
    echo "    missingok"; \
    echo "    rotate 7"; \
    echo "    compress"; \
    echo "    delaycompress"; \
    echo "    notifempty"; \
    echo "    create 640 www-data www-data"; \
    echo "    sharedscripts"; \
    echo "}"; \
    echo ""; \
    echo "/var/log/nginx/*.log {"; \
    echo "    daily"; \
    echo "    missingok"; \
    echo "    rotate 7"; \
    echo "    compress"; \
    echo "    delaycompress"; \
    echo "    notifempty"; \
    echo "    create 640 www-data www-data"; \
    echo "    sharedscripts"; \
    echo "    postrotate"; \
    echo "        [ -f /var/run/nginx.pid ] && kill -USR1 \`cat /var/run/nginx.pid\`"; \
    echo "    endscript"; \
    echo "}"; \
    } > /etc/logrotate.d/nginx_laravel

# Set up cron job for logrotate
RUN { \
    echo "0 0 * * * /usr/sbin/logrotate /etc/logrotate.d/nginx_laravel > /dev/null 2>&1"; \
    } > /etc/cron.d/logrotate
RUN chmod 0644 /etc/cron.d/logrotate
RUN crontab /etc/cron.d/logrotate

# Expose port 80
EXPOSE 80

# Create logs directory with proper permissions
RUN mkdir -p /var/www/storage/logs && chown -R www-data:www-data /var/www/storage/logs

# Start services
CMD ["sh", "-c", "cron && supervisord -c /etc/supervisor/conf.d/supervisord.conf"]
