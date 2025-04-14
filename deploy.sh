#!/bin/sh
set -e  # Exit immediately if a command exits with a non-zero status

# Set default values for main branch
IMAGE_NAME=${APP_NAME}
DB_DATABASE=shopify_idp
APP_URL=https://shop.smart2be.com
APP_ENV=production

# Run Laravel commands directly in the container
echo "Running Laravel configuration commands..."
php artisan config:clear

if [ "${APP_ENV}" = "production" ]; then
    echo "Running migrations in production mode..."
    php artisan migrate --force
else
    echo "Running fresh migrations with seed in development mode..."
    php artisan migrate:fresh --seed
fi

echo "Deployment completed successfully."
