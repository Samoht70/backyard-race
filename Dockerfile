# syntax=docker/dockerfile:1

ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1-php8.4-bookworm
ARG NODE_IMAGE=node:22-bookworm-slim
ARG COMPOSER_IMAGE=composer/composer:2-bin

FROM ${COMPOSER_IMAGE} AS composer-bin
FROM ${NODE_IMAGE} AS node-bin


FROM ${FRANKENPHP_IMAGE} AS base

WORKDIR /app

RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        posix \
        redis \
        zip

COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini

RUN groupadd --gid 1000 app \
 && useradd --uid 1000 --gid app --home-dir /app --no-create-home --shell /usr/sbin/nologin app


FROM base AS vendor

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

COPY --from=composer-bin /composer /usr/local/bin/composer

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
 && composer check-platform-reqs --no-dev \
 && php artisan vendor:publish --tag=laravel-assets --force \
 && mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs


FROM vendor AS assets

COPY --from=node-bin /usr/local/bin/node /usr/local/bin/node
COPY --from=node-bin /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/npm

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

ARG VITE_APP_NAME="Backyard Race"
ENV VITE_APP_NAME=${VITE_APP_NAME}

RUN npm ci --no-audit --no-fund

RUN npm run build \
 && test -f public/build/manifest.json \
 && test -f public/build/fonts-manifest.json


FROM assets AS prune

RUN rm -rf \
        node_modules \
        resources/js \
        resources/css \
        resources/fonts \
        package.json \
        package-lock.json \
        pnpm-workspace.yaml \
        .npmrc \
        vite.config.ts \
        tsconfig.json \
        components.json


FROM base AS final

ENV SERVER_NAME=":8080" \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info

COPY --from=prune --chown=app:app /app /app
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/backyard-entrypoint

RUN chown -R app:app /data/caddy /config/caddy

USER app

EXPOSE 8080
STOPSIGNAL SIGTERM

HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=3 \
    CMD curl --fail --silent --output /dev/null http://127.0.0.1:8080/up

ENTRYPOINT ["backyard-entrypoint"]

CMD ["web"]
