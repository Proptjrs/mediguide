#!/bin/sh
# Démarrage du conteneur sur Render.
set -e

# Render impose le port d'écoute par la variable PORT ; Apache doit s'y plier.
PORT="${PORT:-10000}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# La clé de chiffrement ne doit jamais être régénérée : les antécédents, les
# allergies et les prescriptions sont chiffrés avec elle. Une nouvelle clé les
# rendrait illisibles. Elle est donc fournie par la configuration du service.
if [ -z "${APP_KEY}" ]; then
    echo "ERREUR : la variable APP_KEY n'est pas définie." >&2
    echo "         Sans elle, les données médicales chiffrées seraient perdues." >&2
    exit 1
fi

# Mise à jour du schéma, puis mise en cache de la configuration et des routes.
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
