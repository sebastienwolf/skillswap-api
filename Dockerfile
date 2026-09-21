# Image autonome de l'API : contrairement à Laravel Sail (docker-compose.sail.yml),
# celle-ci embarque directement les dépendances Composer dans l'image. Un simple
# `docker compose up` suffit donc à tout démarrer, sans installation préalable sur
# la machine hôte (voir le README, section "Avec Docker").
FROM php:8.3-cli-alpine

# Uniquement les extensions réellement nécessaires : ext-mbstring (requis par
# laravel/framework, absent par défaut sur cette image) et pdo_sqlite (base de
# données par défaut du projet, cf. .env.example) — pas de service de base de
# données séparé à gérer. Le reste des extensions exigées par Laravel
# (ctype, filter, hash, openssl, session, tokenizer, json) est déjà présent
# nativement dans cette image officielle.
RUN apk add --no-cache \
        sqlite-dev \
        oniguruma-dev \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        mbstring

# Binaire officiel Composer, copié depuis son image dédiée plutôt qu'installé
# via un script téléchargé à la volée.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dépendances installées dans une couche séparée du code applicatif : tant que
# composer.json/composer.lock ne changent pas, Docker réutilise le cache de
# cette étape même si le code source, lui, a changé (builds plus rapides).
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --no-progress \
        --optimize-autoloader

COPY . .

# Filet de sécurité en plus de .dockerignore : si un cache Laravel généré
# localement (ex : bootstrap/cache/packages.php référençant des paquets de
# dev comme laravel/pail, absents ici puisqu'installés avec --no-dev) se
# retrouvait quand même dans le contexte de build, l'image ne doit jamais le
# reprendre tel quel. Laravel régénère ces caches lui-même au premier appel
# artisan si besoin (voir docker/entrypoint.sh).
RUN rm -f bootstrap/cache/*.php

RUN cp -n .env.example .env \
    && touch database/database.sqlite

# Données de démonstration générées dès le build, pour que l'application ait
# des données prêtes dès le premier `docker compose up` (pas seulement après
# le premier démarrage du conteneur) : SQLite étant un fichier, il fait
# partie de la couche d'image et sera repris tel quel par Docker au moment où
# le volume nommé "skillswap-database" (cf. docker-compose.yml) sera créé et
# initialisé à partir du contenu de l'image.
#
# Une clé d'application temporaire est nécessaire pour exécuter `artisan`
# (aucune commande ne fonctionne sans elle), mais elle est effacée aussitôt
# après : une image ne doit jamais contenir de secret généré. Le script
# d'entrée (voir docker/entrypoint.sh) en génère une vraie, propre à chaque
# conteneur, à son tout premier démarrage — sans invalider les données
# ci-dessus, qui ne dépendent pas de cette clé (mots de passe hashés en
# bcrypt, aucune colonne chiffrée dans ce jeu de données).
RUN php artisan key:generate --force \
    && php artisan migrate --seed --force \
    && sed -i 's/^APP_KEY=.*/APP_KEY=/' .env

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
