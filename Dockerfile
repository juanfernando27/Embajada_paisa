# Dockerfile genérico para desplegar un proyecto Laravel en Render (copiar en la raíz del proyecto).
#
# Si el proyecto necesita otra versión de PHP, cámbiela aquí (mire "php" en su composer.json):
ARG PHP_VERSION=8.4

# ---------------------------------------------------------------- base: PHP + Apache + extensiones
FROM php:${PHP_VERSION}-apache AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pdo_mysql bcmath zip intl gd \
    && rm -rf /var/lib/apt/lists/*

# Apache sirve la carpeta public/ y necesita mod_rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

# Render pone el HTTPS por delante de la aplicación: así PHP sabe que la petición original era https
# (sin esto, Laravel generaría enlaces http://).
RUN printf 'SetEnvIf X-Forwarded-Proto "https" HTTPS=on\n' > /etc/apache2/conf-available/https-proxy.conf \
    && a2enconf https-proxy

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# ---------------------------------------------------------------- vendor: dependencias de Composer
FROM base AS vendor
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

# ---------------------------------------------------------------- assets: compila el frontend (Vite/Mix)
# Solo se compila si el proyecto tiene un script "build" en package.json; si no, no hace nada.
FROM node:22-alpine AS assets
WORKDIR /app
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
RUN if [ -f package.json ] && grep -q '"build"' package.json; then \
        (npm ci || npm install) && npm run build; \
    fi

# ---------------------------------------------------------------- imagen final
FROM base
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public ./public
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache database

# Valores por defecto para producción (en Render se pueden cambiar en "Environment")
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    CACHE_DRIVER=file \
    QUEUE_CONNECTION=sync

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["entrypoint.sh"]
