#!/bin/sh
set -eu

REQUIRED="
APP_NAME APP_ENV APP_KEY APP_URL APP_DEBUG
DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
REDIS_CLIENT REDIS_HOST REDIS_PORT
CACHE_STORE QUEUE_CONNECTION SESSION_DRIVER
FILESYSTEM_DISK MEDIA_DISK
AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_DEFAULT_REGION AWS_BUCKET
MAIL_MAILER MAIL_FROM_ADDRESS
"

fail() {
    echo "backyard-race: refusing to start." >&2
    echo "backyard-race: $1" >&2
    exit 78
}

missing=""

for name in $REQUIRED; do
    if [ -z "$(printenv "$name" || true)" ]; then
        missing="$missing $name"
    fi
done

if [ -n "$missing" ]; then
    fail "these environment variables are empty or unset:$missing"
fi

case "$APP_KEY" in
    base64:*) ;;
    *) fail "APP_KEY must be a base64 key, as produced by 'php artisan key:generate --show'." ;;
esac

case "$APP_URL" in
    http://*|https://*) ;;
    *) fail "APP_URL must be absolute and carry its scheme, such as https://backyard-race.example." ;;
esac

if [ "$APP_ENV" = "production" ] && [ "$APP_DEBUG" != "false" ]; then
    fail "APP_DEBUG must be false when APP_ENV is production."
fi

role="${1:-web}"

case "$role" in
    web)
        exec frankenphp run --config /etc/frankenphp/Caddyfile
        ;;
    worker)
        exec php artisan horizon
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
