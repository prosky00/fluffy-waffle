# ── Stage 1: Build frontend assets ────────────────────────────────────────────
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install --ignore-scripts

COPY resources/ resources/
COPY vite.config.js ./
COPY public/ public/

RUN npm run build

# ── Stage 2: PHP runtime ───────────────────────────────────────────────────────
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# System dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    nginx \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    sqlite \
    sqlite-dev \
    mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Copy application source
COPY . .

# Copy built assets from node stage
COPY --from=node-builder /app/public/build public/build

# Install PHP dependencies (no dev, optimised autoloader)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Permissions: storage and bootstrap cache must be writable by www-data (the
# actual user php-fpm's worker processes run as, not root) — this now also
# covers the SQLite file itself, which lives at storage/app/database.sqlite
# rather than under database/ (see docker-compose.yml for why: database/ also
# holds migrations/, which must never be volume-mounted/frozen). Also create
# storage/app/public and the public/storage symlink at build time so uploaded
# files are served correctly regardless of runtime user permissions
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} storage/app/public bootstrap/cache \
    && ln -sf ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint handles first-run setup
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Single-container nginx+php-fpm command, used only by the Render preview
# (docker-compose runs them as two separate containers instead)
COPY docker/nginx/preview.conf.template /etc/nginx/http.d/preview.conf.template
COPY docker/render-cmd.sh /render-cmd.sh
RUN chmod +x /render-cmd.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
