#!/usr/bin/env bash

#
# TYPO3 Extension Test Runner
# Runs various tests for the nr_saml_auth extension
#

set -e

SCRIPT_PATH=$(dirname $(realpath "$0"))
ROOT_PATH=$(realpath "${SCRIPT_PATH}/../..")
BUILD_PATH="${ROOT_PATH}/.Build"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default PHP version
PHP_VERSION="8.2"

# Show usage
usage() {
    echo "Usage: $0 [options] <test-suite>"
    echo ""
    echo "Test Suites:"
    echo "  unit              Run unit tests"
    echo "  functional        Run functional tests"
    echo "  cgl               Run PHP-CS-Fixer code style checks"
    echo "  cgl-fix           Fix code style issues"
    echo "  phpstan           Run PHPStan static analysis"
    echo "  lint              Run PHP linting"
    echo "  composer          Run composer commands"
    echo ""
    echo "Options:"
    echo "  -p <version>      PHP version (8.1, 8.2, 8.3, 8.4) default: ${PHP_VERSION}"
    echo "  -x                Enable Xdebug"
    echo "  -v                Verbose output"
    echo "  -h                Show this help"
    echo ""
    echo "Examples:"
    echo "  $0 unit"
    echo "  $0 -p 8.3 functional"
    echo "  $0 cgl-fix"
    echo "  $0 phpstan"
    exit 0
}

# Install composer dependencies if needed
composerInstall() {
    if [ ! -d "${BUILD_PATH}/vendor" ]; then
        echo -e "${YELLOW}Installing composer dependencies...${NC}"
        composer install --working-dir="${ROOT_PATH}" --no-progress --no-interaction
    fi
}

# Run unit tests
runUnitTests() {
    composerInstall
    echo -e "${GREEN}Running unit tests...${NC}"
    "${BUILD_PATH}/vendor/bin/phpunit" -c "${ROOT_PATH}/Build/phpunit/UnitTests.xml" "$@"
}

# Run functional tests
runFunctionalTests() {
    composerInstall
    echo -e "${GREEN}Running functional tests...${NC}"
    "${BUILD_PATH}/vendor/bin/phpunit" -c "${ROOT_PATH}/Build/phpunit/FunctionalTests.xml" "$@"
}

# Run PHP-CS-Fixer
runCgl() {
    composerInstall
    echo -e "${GREEN}Running PHP-CS-Fixer...${NC}"
    "${BUILD_PATH}/vendor/bin/php-cs-fixer" fix --config="${ROOT_PATH}/.php-cs-fixer.php" --dry-run --diff "$@"
}

# Fix code style
runCglFix() {
    composerInstall
    echo -e "${GREEN}Fixing code style...${NC}"
    "${BUILD_PATH}/vendor/bin/php-cs-fixer" fix --config="${ROOT_PATH}/.php-cs-fixer.php" "$@"
}

# Run PHPStan
runPhpstan() {
    composerInstall
    echo -e "${GREEN}Running PHPStan...${NC}"
    "${BUILD_PATH}/vendor/bin/phpstan" analyse -c "${ROOT_PATH}/phpstan.neon" "$@"
}

# Run PHP linting
runLint() {
    echo -e "${GREEN}Running PHP linting...${NC}"
    find "${ROOT_PATH}/Classes" "${ROOT_PATH}/Configuration" -name "*.php" -print0 | xargs -0 -n1 php -l
}

# Parse options
while getopts "p:xvh" opt; do
    case ${opt} in
        p)
            PHP_VERSION="$OPTARG"
            ;;
        x)
            export XDEBUG_MODE=debug
            ;;
        v)
            VERBOSE="-v"
            ;;
        h)
            usage
            ;;
        \?)
            usage
            ;;
    esac
done

shift $((OPTIND - 1))

# Get test suite
TEST_SUITE="${1:-}"
shift || true

case "$TEST_SUITE" in
    unit)
        runUnitTests "$@"
        ;;
    functional)
        runFunctionalTests "$@"
        ;;
    cgl)
        runCgl "$@"
        ;;
    cgl-fix)
        runCglFix "$@"
        ;;
    phpstan)
        runPhpstan "$@"
        ;;
    lint)
        runLint
        ;;
    composer)
        composer --working-dir="${ROOT_PATH}" "$@"
        ;;
    *)
        usage
        ;;
esac

echo -e "${GREEN}Done!${NC}"
