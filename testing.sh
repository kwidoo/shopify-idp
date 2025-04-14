#!/bin/sh
set -euxo pipefail
echo ">> Starting testing.sh"

# Check if Docker is installed and available
if ! command -v docker >/dev/null 2>&1; then
    echo "Error: Docker is not installed or not in PATH. Please install Docker and try again."
    exit 127
fi

# Set default tag if DRONE_COMMIT_SHA is not available
if [ -z "${DRONE_COMMIT_SHA:-}" ]; then
    TAG_VALUE="local-$(date +%Y%m%d-%H%M%S)"
    echo "DRONE_COMMIT_SHA not set, using tag: ${TAG_VALUE}"
else
    TAG_VALUE=${DRONE_COMMIT_SHA}
    echo "Using DRONE_COMMIT_SHA for tag: ${TAG_VALUE}"
fi

echo "Running tests in Docker container..."
# Run the tests directly without installing composer again (already done in build stage)
docker run --rm \
    -e APP_ENV=testing \
    shopify-oidc:${TAG_VALUE} sh -c "cd /var/www && ./vendor/bin/phpunit"

TEST_EXIT_CODE=$?
echo "Tests completed with exit code: $TEST_EXIT_CODE"
exit $TEST_EXIT_CODE
