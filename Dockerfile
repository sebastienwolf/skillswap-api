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

# Fichier d'environnement et base SQLite : générés une seule fois, à la
# construction de l'image ; le script d'entrée (voir docker/entrypoint.sh)
# se charge de la clé d'application et des migrations au démarrage du
# conteneur, pas ici (une image ne doit pas contenir de secret généré).
RUN cp -n .env.example .env \
    && touch database/database.sqlite

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
