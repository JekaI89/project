#!/bin/sh
set -e

cd /var/www
ROLE="${CONTAINER_ROLE:-app}"

wait_for_vendor() {
  i=0
  while [ ! -f vendor/autoload.php ]; do
    i=$((i + 1))
    if [ "$i" -gt 180 ]; then
      echo "[entrypoint:$ROLE] timeout waiting for vendor/autoload.php"
      exit 1
    fi
    echo "[entrypoint:$ROLE] waiting for vendor... $i"
    sleep 2
  done
}

if [ "$ROLE" = "app" ]; then
  if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint:app] composer install (no-dev, faster on low RAM)..."
    COMPOSER_MEMORY_LIMIT=1G composer install \
      --no-dev \
      --no-interaction \
      --prefer-dist \
      --no-ansi \
      --no-scripts
    COMPOSER_MEMORY_LIMIT=1G composer dump-autoload -o --no-ansi || true
    php artisan package:discover --ansi || true
  else
    echo "[entrypoint:app] vendor already present, skip composer"
  fi

  if [ ! -f .env ]; then
    cp .env.example .env
  fi

  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force || true
  fi

  if [ "${SKIP_MIGRATE:-0}" != "1" ]; then
    echo "[entrypoint:app] migrate..."
    for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do
      if php artisan migrate --force; then
        php artisan db:seed --force 2>/dev/null || true
        break
      fi
      echo "[entrypoint:app] DB not ready, retry $i"
      sleep 2
    done
  fi
else
  wait_for_vendor
fi

echo "[entrypoint:$ROLE] starting: $*"
exec "$@"
