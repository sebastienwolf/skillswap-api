# SkillSwap API

🇫🇷 [Français](#français) · 🇬🇧 [English](#english)

---

<a name="français"></a>

## Français

API REST Laravel permettant à des voisins d'échanger des **objets** et des **compétences** : chacun publie des offres (« je prête ma perceuse », « je donne des cours de guitare ») ou des besoins (« je cherche une perceuse », « je cherche un prof de guitare »), demande une réservation, et un administrateur supervise la plateforme.

### À propos de ce portfolio

Ce dépôt est un **exemple de code** destiné à illustrer ma façon de travailler en tant que développeur Laravel/PHP. Le projet SkillSwap reprend, sur un domaine fonctionnel entièrement original, l'architecture et les pratiques que j'applique au quotidien sur mon dernier poste professionnel (structure en couches, tests, autorisation fine, événements, observers...) — sans réutiliser le moindre code ni la logique métier réelle, que je ne peux évidemment pas publier.

Ce code a été construit avec **Claude Code**, en pilotant plusieurs agents IA tout au long du développement (structure du projet, tests, corrections de bugs, analyse statique, rédaction de ce README). Le choix d'utiliser l'IA ici est volontaire et assumé : je voulais démontrer non seulement mes compétences Laravel/PHP, mais aussi ma capacité à intégrer des outils d'IA générative dans un vrai flux de travail professionnel — en gardant la main sur les décisions d'architecture, en relisant systématiquement chaque modification, et en faisant respecter les conventions du langage ainsi qu'une vérification rigoureuse (tests, analyse statique, style de code) sur tout ce que l'IA produisait.

Autrement dit : l'IA a écrit des lignes de code, mais les choix, la relecture et la validation restent les miens — c'est exactement comme ça que j'envisage d'utiliser ces outils en environnement professionnel.

### Pourquoi ce projet

L'objectif n'est pas de couvrir un maximum de fonctionnalités, mais de montrer sur un périmètre volontairement restreint (deux types d'annonces + un cycle de réservation + un espace admin) :

- une architecture propre et testée plutôt qu'un CRUD générique ;
- des choix techniques assumés et expliqués plutôt que subis ;
- du code qui se lit sans effort.

### Stack technique

| | |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Auth API | Laravel Sanctum (tokens porteurs) |
| Base de données (dev/CI) | SQLite — zéro configuration pour lancer le projet |
| Tests | PHPUnit 11 (tests unitaires + tests fonctionnels HTTP) |
| Qualité | Laravel Pint (style PSR-12) + Larastan/PHPStan niveau 6 |
| Conteneurisation | Image Docker autonome (`docker compose up`) ; Laravel Sail en alternative |
| CI | GitHub Actions (Pint + PHPStan + PHPUnit à chaque push/PR) |

### Architecture et choix de conception

**Un contrat `Exchangeable` partagé par `Item` et `Skill`.**
Un objet et une compétence n'ont presque rien en commun dans leurs colonnes, mais tout en commun dans leur cycle de vie (publié → réservé → terminé, ou archivé). Plutôt que de dupliquer cette logique, `App\Contracts\Exchangeable` définit le contrat, et `App\Models\Concerns\HasExchangeLifecycle` l'implémente une seule fois. `App\Services\MatchingService` et les actions de réservation manipulent ce contrat sans jamais savoir s'ils ont affaire à un `Item` ou un `Skill` (principe ouvert/fermé de SOLID) — grâce aux types d'intersection PHP 8.1 (`Exchangeable&Model`).

**Un query builder Eloquent personnalisé (`ExchangeableQueryBuilder`).**
Les filtres courants (`published()`, `ofType()`, `byCategory()`, `ownedBy()`...) vivent à un seul endroit et s'utilisent en chaîne : `Item::query()->published()->byCategory($id)->paginate()`.

**Des Actions pour la logique qui en vaut la peine, pas pour la façade.**
Créer un item, c'est une simple validation + un `create()` : ça reste dans le contrôleur, sans indirection inutile (YAGNI). En revanche, accepter une réservation implique plusieurs règles (verrouiller la ressource, décliner les autres demandes en attente, notifier) : cette logique vit dans `App\Actions\Reservations\AcceptReservationAction`, testable isolément.

**Policies explicites, sans `Gate::before` global.**
Un raccourci classique consiste à donner un accès total aux administrateurs via `Gate::before`. Ce projet l'évite délibérément : une règle comme « un administrateur ne peut pas modifier son propre compte via l'espace admin » serait sinon silencieusement contournée. Le passe-droit admin est donc explicite, au cas par cas, dans chaque Policy qui le justifie (voir `UserPolicy` pour le cas où il ne s'applique pas).

**Observers + Events + Listeners en file d'attente.**
La publication d'une annonce (`ItemObserver`/`SkillObserver`) déclenche un événement (`ExchangeablePublished`), écouté par un listener mis en file d'attente (`NotifyMatchingMembers`) qui cherche les membres intéressés via `MatchingService` et les notifie. Le contrôleur qui a créé l'annonce n'attend jamais cette recherche.

**Middleware à responsabilité unique.**
`EnsureUserIsAdmin` (rôle) et `EnsureAccountIsActive` (compte désactivé) sont deux vérifications indépendantes, combinées dans les routes plutôt que fusionnées en un middleware « fourre-tout ».

### Domaine fonctionnel

```
User (member | admin)
 ├─ Item      (offer | need)  ── catégorie ── cycle de vie partagé
 ├─ Skill     (offer | need)  ── catégorie ── cycle de vie partagé
 └─ Reservation (polymorphique : porte sur un Item OU un Skill)
      pending → accepted → completed
         │           └────────→ cancelled
         └─ declined
```

### Démarrage rapide (sans Docker)

> Ce dépôt ne contient pas le dossier `vendor/` (dépendances Composer) : c'est la norme pour un projet Laravel versionné, à récupérer après clonage.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

L'API est alors disponible sur `http://localhost:8000/api`. Le seeder crée un administrateur (`admin@skillswap.test` / `password`), plusieurs membres, des catégories, des objets et des compétences de démonstration.

### Avec Docker

Rien à installer sur la machine hôte : l'image embarque directement les dépendances Composer, et le conteneur se charge lui-même de la clé d'application et des migrations à son premier démarrage.

```bash
docker compose up
```

L'API est alors disponible sur `http://localhost:8000/api` (le port se change via `APP_PORT` dans un fichier `.env` à la racine). Les données SQLite sont conservées dans un volume Docker nommé : un `docker compose down` suivi d'un `up` ne perd donc pas les données déjà créées.

> ⚠️ Les logs affichent `Server running on [http://0.0.0.0:8000]` : `0.0.0.0` signifie que le serveur écoute sur toutes les interfaces *à l'intérieur du conteneur*, ce n'est pas une adresse à laquelle se connecter depuis le navigateur (elle renverrait une erreur `ERR_ADDRESS_INVALID`). Utilisez bien `http://localhost:8000/api`. Il n'y a par ailleurs aucune route sur `/` : c'est une API, pas un site web, donc une 404 sur `http://localhost:8000/` seul est normale — testez par exemple `http://localhost:8000/api/items`.

Pour une stack plus proche d'une production réelle (MySQL, Redis, Mailpit en services séparés), l'alternative Laravel Sail reste disponible :

```bash
composer install
cp .env.example .env
docker compose -f docker-compose.sail.yml up -d
docker compose -f docker-compose.sail.yml exec laravel.test php artisan key:generate
docker compose -f docker-compose.sail.yml exec laravel.test php artisan migrate --seed
```

### Qualité et tests

```bash
composer test         # Suite PHPUnit (unitaires + fonctionnels)
composer format        # Corrige le style de code (Laravel Pint)
composer format-test   # Vérifie le style sans corriger (utilisé en CI)
composer analyse       # Analyse statique (Larastan, niveau 6)
composer quality       # Les trois d'affilée, comme en CI
```

La CI GitHub Actions (`.github/workflows/ci.yml`) exécute ces trois étapes à chaque push et pull request.

### Aperçu de l'API

Toutes les routes sont préfixées par `/api`. Authentification par token Sanctum (`Authorization: Bearer <token>`).

| Méthode | Route | Accès | Description |
|---|---|---|---|
| POST | `/auth/register` | public | Inscription |
| POST | `/auth/login` | public | Connexion, retourne un token |
| POST | `/auth/logout` | authentifié | Révoque le token courant |
| GET | `/auth/me` | authentifié | Profil courant |
| GET | `/categories` | public | Liste des catégories |
| GET, POST | `/items` | public / authentifié | Catalogue des objets / publication |
| PATCH, DELETE | `/items/{item}` | propriétaire | Modification / suppression |
| POST | `/items/{item}/publish`, `/archive` | propriétaire | Cycle de vie |
| GET, POST | `/skills` | public / authentifié | Idem pour les compétences |
| GET, POST | `/reservations` | authentifié | Mes demandes / réservations reçues (`?scope=received`) |
| POST | `/reservations/{r}/accept`, `/decline`, `/cancel`, `/complete` | propriétaire ou demandeur | Cycle de vie d'une réservation |
| GET | `/admin/dashboard` | admin | Statistiques globales |
| GET | `/admin/items`, `/admin/skills` | admin | Modération (tous statuts) |
| CRUD | `/admin/categories` | admin | Gestion de la taxonomie |
| GET, PATCH, DELETE | `/admin/users` | admin | Gestion des comptes (rôle, activation) |

### Ce que je referais différemment à plus grande échelle

- un système de permissions plus riche (type `spatie/laravel-permission`) si le nombre de rôles augmentait — ici, deux rôles ne le justifient pas (YAGNI) ;
- un vrai driver de file d'attente (Redis) plutôt que la file basée sur la base de données, au-delà d'un usage de démonstration ;
- de la pagination par curseur sur les listings à fort volume.

---

<a name="english"></a>

## English

A Laravel REST API letting neighbours exchange **items** and **skills**: everyone posts offers (“I'll lend my drill”, “I teach guitar lessons”) or needs (“looking for a drill”, “looking for a guitar teacher”), requests a reservation, and an administrator oversees the platform.

### About this portfolio

This repository is a **code sample** meant to illustrate how I work as a Laravel/PHP developer. The SkillSwap project reproduces, on an entirely original functional domain, the architecture and practices I apply day to day on my current professional role (layered structure, tests, fine-grained authorization, events, observers...) — without reusing any of the actual code or business logic, which I obviously can't publish.

This code was built with **Claude Code**, directing several AI agents throughout development (project structure, tests, bug fixes, static analysis, and this very README). Using AI here was a deliberate choice: I wanted to demonstrate not only my Laravel/PHP skills, but also my ability to integrate generative AI tools into a real professional workflow — staying in control of architectural decisions, systematically reviewing every change, and enforcing language conventions along with rigorous verification (tests, static analysis, code style) on everything the AI produced.

In other words: the AI wrote lines of code, but the decisions, the review, and the validation are mine — which is exactly how I intend to use these tools in a professional environment.

### Why this project

The goal isn't to cover as many features as possible, but to demonstrate, on a deliberately narrow scope (two listing types + a reservation lifecycle + an admin area):

- a clean, tested architecture rather than a generic CRUD;
- deliberate, explained technical choices rather than accidental ones;
- code that reads effortlessly.

### Tech stack

| | |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| API auth | Laravel Sanctum (bearer tokens) |
| Database (dev/CI) | SQLite — zero configuration to run the project |
| Tests | PHPUnit 11 (unit tests + HTTP feature tests) |
| Quality | Laravel Pint (PSR-12 style) + Larastan/PHPStan level 6 |
| Containerization | Standalone Docker image (`docker compose up`); Laravel Sail as an alternative |
| CI | GitHub Actions (Pint + PHPStan + PHPUnit on every push/PR) |

### Architecture and design choices

**An `Exchangeable` contract shared by `Item` and `Skill`.**
An item and a skill have almost nothing in common in their columns, but everything in common in their lifecycle (published → reserved → completed, or archived). Rather than duplicating that logic, `App\Contracts\Exchangeable` defines the contract, and `App\Models\Concerns\HasExchangeLifecycle` implements it once. `App\Services\MatchingService` and the reservation actions work against this contract without ever knowing whether they're dealing with an `Item` or a `Skill` (SOLID's open/closed principle) — made possible by PHP 8.1 intersection types (`Exchangeable&Model`).

**A custom Eloquent query builder (`ExchangeableQueryBuilder`).**
Common filters (`published()`, `ofType()`, `byCategory()`, `ownedBy()`...) live in a single place and chain naturally: `Item::query()->published()->byCategory($id)->paginate()`.

**Actions for logic that earns its keep, not for the sake of it.**
Creating an item is just validation plus a `create()`: it stays in the controller, with no unnecessary indirection (YAGNI). Accepting a reservation, on the other hand, involves several rules (locking the resource, declining other pending requests, notifying): that logic lives in `App\Actions\Reservations\AcceptReservationAction`, independently testable.

**Explicit policies, no global `Gate::before`.**
A common shortcut is granting administrators blanket access via `Gate::before`. This project deliberately avoids that: a rule like "an admin can't edit their own account through the admin area" would otherwise be silently bypassed. The admin override is therefore explicit, on a case-by-case basis, in every Policy where it's actually justified (see `UserPolicy` for the case where it doesn't apply).

**Observers + Events + queued Listeners.**
Publishing a listing (`ItemObserver`/`SkillObserver`) dispatches an event (`ExchangeablePublished`), picked up by a queued listener (`NotifyMatchingMembers`) that looks up interested members via `MatchingService` and notifies them. The controller that created the listing never waits on that lookup.

**Single-responsibility middleware.**
`EnsureUserIsAdmin` (role) and `EnsureAccountIsActive` (deactivated account) are two independent checks, combined at the route level rather than merged into one catch-all middleware.

### Functional domain

```
User (member | admin)
 ├─ Item      (offer | need)  ── category ── shared lifecycle
 ├─ Skill     (offer | need)  ── category ── shared lifecycle
 └─ Reservation (polymorphic: targets either an Item OR a Skill)
      pending → accepted → completed
         │           └────────→ cancelled
         └─ declined
```

### Quick start (without Docker)

> This repository doesn't include the `vendor/` folder (Composer dependencies): that's standard for a versioned Laravel project, and it's fetched right after cloning.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

The API is then available at `http://localhost:8000/api`. The seeder creates an administrator (`admin@skillswap.test` / `password`), several members, categories, and demo items and skills.

### With Docker

Nothing to install on the host machine: the image bundles the Composer dependencies directly, and the container handles the application key and migrations itself on first startup.

```bash
docker compose up
```

The API is then available at `http://localhost:8000/api` (the port is configurable via `APP_PORT` in a root `.env` file). SQLite data is kept in a named Docker volume, so a `docker compose down` followed by `up` does not lose data already created.

> ⚠️ The logs show `Server running on [http://0.0.0.0:8000]`: `0.0.0.0` means the server listens on every interface *inside the container*, it is not an address you can open in a browser (it would raise an `ERR_ADDRESS_INVALID` error). Use `http://localhost:8000/api` instead. There is also no route on `/`: this is an API, not a website, so a 404 on `http://localhost:8000/` alone is expected — try `http://localhost:8000/api/items` instead.

For a stack closer to a real production setup (MySQL, Redis, Mailpit as separate services), the Laravel Sail alternative is still available:

```bash
composer install
cp .env.example .env
docker compose -f docker-compose.sail.yml up -d
docker compose -f docker-compose.sail.yml exec laravel.test php artisan key:generate
docker compose -f docker-compose.sail.yml exec laravel.test php artisan migrate --seed
```

### Quality and tests

```bash
composer test         # PHPUnit suite (unit + feature tests)
composer format        # Fixes code style (Laravel Pint)
composer format-test   # Checks style without fixing (used in CI)
composer analyse       # Static analysis (Larastan, level 6)
composer quality       # All three in a row, same as CI
```

The GitHub Actions CI pipeline (`.github/workflows/ci.yml`) runs these three steps on every push and pull request.

### API overview

All routes are prefixed with `/api`. Authentication uses Sanctum bearer tokens (`Authorization: Bearer <token>`).

| Method | Route | Access | Description |
|---|---|---|---|
| POST | `/auth/register` | public | Register |
| POST | `/auth/login` | public | Log in, returns a token |
| POST | `/auth/logout` | authenticated | Revokes the current token |
| GET | `/auth/me` | authenticated | Current profile |
| GET | `/categories` | public | List categories |
| GET, POST | `/items` | public / authenticated | Item catalogue / publishing |
| PATCH, DELETE | `/items/{item}` | owner | Update / delete |
| POST | `/items/{item}/publish`, `/archive` | owner | Lifecycle |
| GET, POST | `/skills` | public / authenticated | Same for skills |
| GET, POST | `/reservations` | authenticated | My requests / reservations received (`?scope=received`) |
| POST | `/reservations/{r}/accept`, `/decline`, `/cancel`, `/complete` | owner or requester | Reservation lifecycle |
| GET | `/admin/dashboard` | admin | Global statistics |
| GET | `/admin/items`, `/admin/skills` | admin | Moderation (all statuses) |
| CRUD | `/admin/categories` | admin | Taxonomy management |
| GET, PATCH, DELETE | `/admin/users` | admin | Account management (role, activation) |

### What I'd do differently at a larger scale

- a richer permission system (e.g. `spatie/laravel-permission`) if the number of roles grew — with just two roles here, it isn't justified (YAGNI);
- a real queue driver (Redis) instead of the database-backed queue, beyond demo usage;
- cursor-based pagination on high-volume listings.

---

Project built by Sébastien (furi) as a demonstration of Laravel/PHP skills.
