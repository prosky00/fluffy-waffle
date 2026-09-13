#!/bin/bash
set -e

echo "==> Starting Faction Dashboard..."

INSTALLED_LOCK="storage/installed.lock"

# ── Make .env writable so the install wizard can write to it ─────────────────
touch .env 2>/dev/null || true
chmod 664 .env 2>/dev/null || true

# ── Ensure a valid APP_KEY is set in THIS process's environment ──────────────
# php artisan key:generate only ever writes the .env FILE. That's a no-op on
# hosts that inject env vars directly (Render, etc.): phpdotenv never overrides
# an env var that's already set, so a bad/empty platform-provided APP_KEY wins
# over anything key:generate writes to disk, and Laravel fails at boot with
# "Unsupported cipher or incorrect key length." Exporting a real value here,
# before exec, is what actually reaches the PHP process either way.
if [ -z "$APP_KEY" ] || ! echo "$APP_KEY" | grep -qE '^base64:[A-Za-z0-9+/]{43}=?$'; then
    echo "==> No valid APP_KEY in the environment — generating one for this boot"
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

# ── SQLite: ensure database directory and file exist ─────────────────────────
# Checked against the real process environment (not the .env file) so this also
# works on hosts that inject env vars directly instead of writing a .env file.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown www-data:www-data "$DB_FILE" 2>/dev/null || true
fi

# ── Demo mode: auto-install with a known admin account instead of the wizard ──
# Opt-in only (DEMO_SEED=true) — this creates a user with a published password,
# so it must never be set on a real deployment. Safe to re-run on every boot:
# migrate and the seeder's updateOrCreate are both idempotent.
if [ "${DEMO_SEED:-false}" = "true" ] && [ ! -f "$INSTALLED_LOCK" ]; then
    echo "==> DEMO_SEED enabled — auto-installing with a demo admin account (demo/demo12345)..."
    php artisan migrate --force
    php artisan db:seed --class="Database\\Seeders\\DemoSeeder" --force
    date -u +%Y-%m-%dT%H:%M:%SZ > "$INSTALLED_LOCK"
fi

if [ -f "$INSTALLED_LOCK" ]; then
    # ── Already installed: run any pending migrations and warm caches ──────────
    echo "==> Already installed. Running pending migrations..."
    php artisan migrate --force

    if [ ! -L "public/storage" ]; then
        php artisan storage:link
    fi

    if [ "$APP_ENV" = "production" ]; then
        echo "==> Caching config, routes, and views..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi
else
    # ── First boot: prepare for install wizard ────────────────────────────────
    echo "==> Not installed yet — waiting for install wizard at http://<host>/install"
    # A valid APP_KEY is already guaranteed above, so sessions/CSRF work for the
    # wizard immediately — nothing else to do until the form is submitted.
fi

# ── Fix permissions one final time (volumes may reset ownership) ──────────────
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "==> Ready. Starting PHP-FPM..."
exec "$@"
