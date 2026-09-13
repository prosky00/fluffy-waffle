#!/bin/bash
set -e

# Single-container nginx + php-fpm, used only for the Render preview (docker-compose
# runs them as two real containers instead — see docker/nginx/default.conf for that).
# Render assigns the port to listen on via $PORT, so it's substituted in at boot
# rather than baked into the image.

: "${PORT:=10000}"
sed "s/__PORT__/${PORT}/" /etc/nginx/http.d/preview.conf.template > /etc/nginx/http.d/default.conf

php-fpm -D

echo "==> nginx listening on 0.0.0.0:${PORT}, proxying to php-fpm on 127.0.0.1:9000"
exec nginx -g 'daemon off;'
