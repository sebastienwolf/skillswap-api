#!/bin/sh
# Point d'entrée du conteneur : prépare l'application avant de lancer la
# commande passée en CMD (le serveur de développement Laravel par défaut).
# Idempotent à dessein : ce script s'exécute à *chaque* démarrage du
# conteneur, y compris les redémarrages, jamais uniquement à la création.
set -e

# Clé d'application : générée uniquement si absente, pour ne jamais écraser
# une valeur déjà en place (ex : conteneur redémarré sur un volume déjà
# initialisé, où réécrire APP_KEY invaliderait sessions et cookies chiffrés).
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

# Premier démarrage (base SQLite absente ou vide, ex : volume fraîchement
# créé) : migrations puis jeu de données de démonstration. Démarrages
# suivants : migrations seules, pour ne jamais écraser des données réelles.
first_run=false
if [ ! -s database/database.sqlite ]; then
    first_run=true
fi

touch database/database.sqlite

if [ "$first_run" = true ]; then
    php artisan migrate --seed --force
else
    php artisan migrate --force
fi

exec "$@"
