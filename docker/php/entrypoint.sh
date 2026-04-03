#!/bin/sh
set -e

# Garante que o diretório var/ tenha permissão de escrita
if [ -d /var/www/html/var ]; then
    chown -R www-data:www-data /var/www/html/var
fi

exec "$@"
