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

# Le manifeste des paquets est reconstruit à chaque démarrage : un manifeste
# hérité d'une image précédente peut désigner un paquet qui n'est plus installé,
# et l'application refuse alors de démarrer.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
      bootstrap/cache/config.php bootstrap/cache/routes-*.php
php artisan package:discover --ansi

# Mise à jour du schéma, puis peuplement du référentiel du district. Le semis
# s'interrompt de lui-même si les données sont déjà là : il peut donc être
# rejoué à chaque démarrage sans rien dupliquer.
php artisan migrate --force
php artisan db:seed --force

# Le semis principal s'arrête dès que le référentiel existe : sur une base déjà
# en service, un praticien ou une structure ajoutés depuis n'y entreraient jamais.
# Celui-ci complète le réseau sans rien dupliquer ni écraser, et se rejoue donc à
# chaque démarrage. C'est ce qui met la base en ligne au niveau de la locale.
php artisan db:seed --class=ReseauMedicalSeeder --force \
    || echo "ATTENTION : le réseau de soins n'a pas pu être complété — le site démarre malgré tout."

# Jeu de démonstration : historique de consultations, questionnaires, et le
# compte du secrétariat. Il ne se pose que si DONNEES_DEMO vaut « true », et
# il se refuse lui-même dès qu'un historique existe : le rejouer ne duplique
# rien. C'est le seul moyen de peupler la base sur une offre sans console.
# « set -e » interromprait le démarrage à la moindre erreur du semis, et le
# site tomberait pour un jeu de démonstration — ce qui est arrivé une fois.
# L'échec est donc signalé, mais le conteneur poursuit son démarrage.
if [ "${DONNEES_DEMO:-false}" = "true" ]; then
    php artisan db:seed --class=DemonstrationSeeder --force \
        || echo "ATTENTION : le jeu de démonstration n'a pas pu être posé — le site démarre malgré tout."
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
