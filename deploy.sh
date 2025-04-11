#!/bin/bash
set -e  # Exit immediately if a command exits with a non-zero status

# Set default values for main branch
IMAGE_NAME=shopify-oidc
DB_DATABASE=shopify_oidc
APP_URL=https://shop.smart2be.com
APP_ENV=production

# Override values for sprint branches
case "${DRONE_BRANCH}" in
  sprint-*)
    IMAGE_NAME=shopify-oidc-${DRONE_BRANCH}
    DB_DATABASE=shopify_oidc_s$(echo ${DRONE_BRANCH} | sed 's/sprint-//')
    APP_URL=https://shop-${DRONE_BRANCH}.smart2be.com
    APP_ENV=development
    echo "Using development environment"
    ;;
  *)
    echo "Using production environment"
    ;;
esac

echo "Branch: ${DRONE_BRANCH}"
echo "Image Name: ${IMAGE_NAME}"
echo "Database Name: ${DB_DATABASE}"
echo "App URL: ${APP_URL}"
echo "App Environment: ${APP_ENV}"

# Stop and remove the existing container if it exists
EXISTING_CONTAINER=$(docker ps -q -f name=^${IMAGE_NAME}$)

if [ -n "$EXISTING_CONTAINER" ]; then
    echo "Stopping and removing the existing container: ${IMAGE_NAME}"
    docker stop $EXISTING_CONTAINER
    docker rm $EXISTING_CONTAINER
else
    echo "No existing container named ${IMAGE_NAME} found."
fi

# Run the new container
docker run --rm -d \
    --name $IMAGE_NAME \
    --hostname $IMAGE_NAME \
    --network proxy-network \
    -e APP_URL=$APP_URL \
    -e DB_DATABASE=$DB_DATABASE \
    -e DB_PASSWORD=$DB_PASSWORD \
    -e APP_ENV=$APP_ENV \
    -e SHOPIFY_CLIENT_ID=$SHOPIFY_CLIENT_ID \
    -e SHOPIFY_CLIENT_SECRET=$SHOPIFY_CLIENT_SECRET \
    -e SHOPIFY_REDIRECT_URI=$SHOPIFY_REDIRECT_URI \
    -e SHOPIFY_SHOP_DOMAIN=$SHOPIFY_SHOP_DOMAIN \
    -e SHOPIFY_AUTH_ENDPOINT=$SHOPIFY_AUTH_ENDPOINT \
    -e SHOPIFY_TOKEN_ENDPOINT=$SHOPIFY_TOKEN_ENDPOINT \
    -e SHOPIFY_USERINFO_ENDPOINT=$SHOPIFY_USERINFO_ENDPOINT \
    -e SHOPIFY_JWKS_URI=$SHOPIFY_JWKS_URI \
    -e SHOPIFY_SCOPES=$SHOPIFY_SCOPES \
    -e SHOPIFY_WEBHOOK_SECRET=$SHOPIFY_WEBHOOK_SECRET \
    shopify-oidc:${DRONE_COMMIT_SHA}

# Run Laravel commands inside the container
echo "Running Laravel configuration commands..."
docker exec $IMAGE_NAME sh -c 'php artisan config:clear'

if [ "${DRONE_BRANCH}" = "main" ]; then
    echo "Running migrations in production mode..."
    docker exec $IMAGE_NAME sh -c 'php artisan migrate --force'
else
    echo "Running fresh migrations with seed in development mode..."
    docker exec $IMAGE_NAME sh -c 'php artisan migrate:fresh --seed'
fi

echo "Deployment completed successfully."
