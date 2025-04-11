#!/bin/bash
# Set default environment to stage
export APP_ENV=stage

# Check if testing is requested by environment variable
if [ "${TEST_MODE}" = "true" ]; then
    export APP_ENV=testing
    echo "Building for testing environment"
elif [ "${DRONE_BRANCH}" = "main" ]; then
    export APP_ENV=main
    echo "Building for main environment"
else
    echo "Building for stage environment"
fi

echo "Building with APP_ENV=${APP_ENV}"
docker build \
    --build-arg APP_ENV=${APP_ENV} \
    -t shopify-oidc:${DRONE_COMMIT_SHA} -f Dockerfile .
