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

# Davis-Materialworks fork: rebuild the frontend on deploy. Vito serves the
# pre-built bundle from public/build, and stock update.sh never rebuilt it, so
# any committed JS/TS change stayed invisible until someone built and committed
# assets by hand. Build here so pushing to the branch is enough. Non-fatal: if
# node is missing or the build fails we keep the committed assets and continue.
echo "Building frontend assets..."
if command -v npm >/dev/null 2>&1; then
  # --include=dev: vite and friends are devDependencies and are required to build.
  npm ci --include=dev --no-audit --no-fund || npm install --include=dev --no-audit --no-fund
  if npm run build; then
    echo "Frontend assets built."
  else
    echo "⚠️  Frontend build failed — keeping existing public/build assets."
  fi
else
  echo "⚠️  npm not found — skipping frontend build (serving committed public/build assets)."
fi

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
