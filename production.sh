#!/bin/sh
set -e  # Exit immediately if a command exits with a non-zero status

# Set default values for production environment
IMAGE_NAME=shopify-oidc
DB_DATABASE=shopify_oidc
APP_URL=https://shop.smart2be.com
APP_ENV=production

echo "Using production environment"
echo "Branch: ${DRONE_BRANCH}"
echo "Image Name: ${IMAGE_NAME}"
echo "Database Name: ${DB_DATABASE}"
echo "App Environment: ${APP_ENV}"
echo "App URL: ${APP_URL}"

# Run Laravel configuration commands directly in the container
echo "Running Laravel configuration commands..."
php artisan config:clear

# Run database migrations in production mode
echo "Running migrations in production mode..."
php artisan migrate --force

echo "Production deployment completed successfully."
