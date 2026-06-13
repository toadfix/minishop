#!/usr/bin/env bash
#
# fresh-host.sh — scaffold a throwaway Laravel host app and install Minishop
# into it from this local checkout, so you can develop the package against a
# running app.
#
# The host app is created as a SIBLING of the package directory and the package
# is wired in via a Composer "path" repository, so it is symlinked: edits to the
# package source are live in the host app on the next request.
#
# Usage:
#   scripts/fresh-host.sh [host-dir]
#
#   host-dir   Name (or path) of the host app to create.
#              Default: ../minishop-app relative to the package root.
#
# Re-running against an existing host app re-installs the package without
# recreating Laravel (pass a fresh directory name for a clean slate).
#
# Requirements: php, composer.

set -euo pipefail

# Resolve the package root (parent of this script's directory).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PKG_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
PKG_NAME="$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo $j["name"];' "${PKG_DIR}/composer.json")"

# Resolve the host app directory (default: sibling ../minishop-app).
HOST_ARG="${1:-../minishop-app}"
case "${HOST_ARG}" in
    /*) HOST_DIR="${HOST_ARG}" ;;            # absolute path
    *)  HOST_DIR="$(cd "${PKG_DIR}/.." && pwd)/$(basename "${HOST_ARG}")" ;;
esac

echo "==> Package:  ${PKG_NAME} (${PKG_DIR})"
echo "==> Host app: ${HOST_DIR}"

if [ ! -d "${HOST_DIR}" ]; then
    echo "==> Creating fresh Laravel app..."
    composer create-project laravel/laravel "${HOST_DIR}"
else
    echo "==> Host app already exists — reusing it."
fi

cd "${HOST_DIR}"

echo "==> Wiring in the package via path repository (${PKG_DIR})..."
composer config repositories.minishop path "${PKG_DIR}"
composer config minimum-stability dev
composer config prefer-stable true
composer require "${PKG_NAME}:@dev"

echo "==> Running minishop:install..."
php artisan minishop:install

echo ""
echo "==> Done. Start the app with:"
echo "      cd ${HOST_DIR} && php artisan serve"
echo "    Then open http://localhost:8000/dashboard/login"
