#!/bin/sh
set -euxo pipefail
echo ">> Starting build.sh"
# Check if Docker is installed and available
if ! command -v docker &> /dev/null; then
    echo "Error: Docker is not installed or not in PATH. Please install Docker and try again."
    exit 127
fi

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

# Set a default tag if DRONE_COMMIT_SHA is not available
if [ -z "${DRONE_COMMIT_SHA}" ]; then
    TAG_VALUE="local-$(date +%Y%m%d-%H%M%S)"
    echo "DRONE_COMMIT_SHA not set, using tag: ${TAG_VALUE}"
else
    TAG_VALUE=${DRONE_COMMIT_SHA}
    echo "Using DRONE_COMMIT_SHA for tag: ${TAG_VALUE}"
fi

echo "Building with APP_ENV=${APP_ENV}"
docker build \
    --build-arg APP_ENV=${APP_ENV} \
    -t shopify-oidc:${TAG_VALUE} -f Dockerfile .
