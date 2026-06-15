#!/bin/bash

echo "Updating Vito..."

cd /home/vito/vito

echo "Discarding any possible local changes..."
git reset --hard HEAD
git clean -fd

# Davis-Materialworks fork: deploy the branch tip (which carries our custom
# commits) instead of the latest upstream tag. A positional arg overrides the
# branch; the --alpha/--beta/--rc flags are accepted for compatibility with
# upgrade-3x-to-4x.sh and ignored, since the branch already tracks the release we run.
BRANCH="4.x"
for arg in "$@"; do
  case "$arg" in
    --alpha|--beta|--rc|--*) ;;
    *) BRANCH="$arg" ;;
  esac
done

echo "Pulling latest from origin/$BRANCH..."
git fetch origin --tags --prune
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

NEW_RELEASE="$BRANCH @ $(git rev-parse --short HEAD)"

echo "Installing composer dependencies..."
composer install --no-dev

echo "Running migrations..."
php artisan migrate --force

echo "Optimizing..."
php artisan optimize:clear
php artisan optimize

echo "Restarting workers..."
sudo supervisorctl restart worker:*
sudo supervisorctl restart websocket 2>/dev/null || true

bash scripts/post-update.sh

echo "✅ Vito updated successfully to $NEW_RELEASE! 🎉"
