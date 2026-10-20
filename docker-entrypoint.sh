#!/bin/sh
# docker-entrypoint.sh
# Configures Apache to listen on Render's assigned PORT (default: 10000)
# then hands off to the default CMD (apache2-foreground).

set -e

PORT="${PORT:-10000}"

# Update Apache to listen on the Render-assigned port
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\${APACHE_PORT}/${PORT}/g" /etc/apache2/sites-available/000-default.conf

echo "[iCareDonate] Apache configured on port ${PORT}"

exec "$@"
