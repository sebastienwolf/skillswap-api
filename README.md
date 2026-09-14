# SkillSwap API

API REST Laravel permettant à des voisins d'échanger des **objets** et des **compétences** : chacun publie des offres (« je prête ma perceuse », « je donne des cours de guitare ») ou des besoins (« je cherche une perceuse », « je cherche un prof de guitare »), demande une réservation, et un administrateur supervise la plateforme.

Ce projet est un **portfolio personnel**. Il reproduit, sur un domaine fonctionnel original, l'architecture et les pratiques que j'utilise au quotidien en tant que développeur Laravel/PHP (structure en couches, tests, autorisation fine, événements, observers...) — sans reprendre le code ni la logique métier d'aucun projet client ou employeur.

## Pourquoi ce projet

L'objectif n'est pas de couvrir un maximum de fonctionnalités, mais de montrer sur un périmètre volontairement restreint (deux types d'annonces + un cycle de réservation + un espace admin) :

- une architecture propre et testée plutôt qu'un CRUD générique ;
- des choix techniques assumés et expliqués plutôt que subis ;
- du code qui se lit sans effort.

## Stack technique

| | |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Auth API | Laravel Sanctum (tokens porteurs) |
| Base de données (dev/CI) | SQLite — zéro configuration pour lancer le projet |
| Tests | PHPUnit 11 (tests unitaires + tests fonctionnels HTTP) |
| Qualité | Laravel Pint (style PSR-12) + Larastan/PHPStan niveau 6 |
| Conteneurisation | Laravel Sail (Docker, optionnel) |
| CI | GitHub Actions (Pint + PHPStan + PHPUnit à chaque push/PR) |

## Architecture et choix de conception

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

## Domaine fonctionnel

```
User (member | admin)
 ├─ Item      (offer | need)  ── catégorie ── cycle de vie partagé
 ├─ Skill     (offer | need)  ── catégorie ── cycle de vie partagé
 └─ Reservation (polymorphique : porte sur un Item OU un Skill)
      pending → accepted → completed
         │           └────────→ cancelled
         └─ declined
```

## Démarrage rapide (sans Docker)

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

## Avec Docker (Laravel Sail)

```bash
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

## Qualité et tests

```bash
composer test       # Suite PHPUnit (unitaires + fonctionnels)
composer format      # Corrige le style de code (Laravel Pint)
composer format-test  # Vérifie le style sans corriger (utilisé en CI)
composer analyse      # Analyse statique (Larastan, niveau 6)
composer quality      # Les trois d'affilée, comme en CI
```

La CI GitHub Actions (`.github/workflows/ci.yml`) exécute ces trois étapes à chaque push et pull request.

## Aperçu de l'API

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

## Ce que je referais différemment à plus grande échelle

- un système de permissions plus riche (type `spatie/laravel-permission`) si le nombre de rôles augmentait — ici, deux rôles ne le justifient pas (YAGNI) ;
- un vrai driver de file d'attente (Redis) plutôt que la file basée sur la base de données, au-delà d'un usage de démonstration ;
- de la pagination par curseur sur les listings à fort volume.

---

Projet réalisé par Sébastien (furi) comme démonstration de compétences Laravel/PHP.
