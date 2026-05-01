#!/usr/bin/env sh
set -eu

fail() {
    printf '\n[deploy] ERROR: %s\n' "$1" >&2
    exit 1
}

run() {
    printf '\n[deploy] %s\n' "$*"
    "$@" || fail "Command failed: $*"
}

if [ ! -f .env.prod.local ]; then
    fail ".env.prod.local is missing. Create it on the VPS, then chmod 600 .env.prod.local."
fi

run git pull --ff-only
if ! docker network inspect web >/dev/null 2>&1; then
    run docker network create web
fi

run mkdir -p public/uploads var
run chmod -R a+rwX public/uploads var
run docker compose --env-file .env.prod.local -f compose.prod.yaml build --pull
run docker compose --env-file .env.prod.local -f compose.prod.yaml up -d database php nginx
run docker compose --env-file .env.prod.local -f compose.prod.yaml exec -T --user root php chown -R www-data:www-data /var/www/html/var
run docker compose --env-file .env.prod.local -f compose.prod.yaml exec -T php composer install --prefer-dist --no-dev --no-interaction --no-progress --optimize-autoloader
run docker compose --env-file .env.prod.local -f compose.prod.yaml exec -T php php bin/console doctrine:migrations:migrate --no-interaction --env=prod
run docker compose --env-file .env.prod.local -f compose.prod.yaml exec -T php php bin/console cache:clear --env=prod --no-debug
run docker compose --env-file .env.prod.local -f compose.prod.yaml exec -T php php bin/console cache:warmup --env=prod --no-debug
run docker compose --env-file .env.prod.local -f compose.prod.yaml exec -T --user root php chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads
run docker compose --env-file .env.prod.local -f compose.prod.yaml up -d --no-deps --build php nginx
run docker compose --env-file .env.prod.local -f compose.prod.yaml exec -T --user root php chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads

printf '\n[deploy] OK: SoundOfMemories production containers are running.\n'
