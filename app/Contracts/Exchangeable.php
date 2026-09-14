<?php

namespace App\Contracts;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Contrat commun à toute entité pouvant être échangée entre membres
 * (un objet, une compétence, demain peut-être un service).
 *
 * Grâce à cette interface, App\Services\MatchingService ou les Actions de
 * réservation peuvent manipuler indifféremment un Item ou un Skill sans
 * connaître leur classe concrète (principe d'inversion de dépendance / D
 * de SOLID), et sans le moindre `instanceof` dispersé dans le code.
 *
 * Volontairement pas de propriétés magiques exposées ici (ex: $user_id,
 * $category_id) : une interface n'étend pas Eloquent\Model, donc l'analyse
 * statique ne peut pas garantir leur présence via un simple accès
 * `$exchangeable->user_id`. On expose à la place des méthodes d'accès
 * explicites (ownerId(), categoryId(), ownerUser()), aussi lisibles et
 * réellement vérifiables.
 */
interface Exchangeable
{
    /**
     * @return BelongsTo<User, Model>
     */
    public function owner(): BelongsTo;

    /**
     * @return BelongsTo<Category, Model>
     */
    public function category(): BelongsTo;

    /**
     * Réservations reçues sur cette ressource (relation polymorphique).
     * Utilisé notamment par les Actions de réservation, qui manipulent
     * l'annonce concernée uniquement via ce contrat.
     *
     * @return MorphMany<Reservation, Model>
     */
    public function reservations(): MorphMany;

    /**
     * Identifiant du propriétaire (colonne `user_id`).
     */
    public function ownerId(): int;

    /**
     * Identifiant de la catégorie (colonne `category_id`).
     */
    public function categoryId(): int;

    /**
     * Le propriétaire de cette ressource, déjà chargé (voir la relation
     * `owner()` et son eager loading dans MatchingService).
     */
    public function ownerUser(): User;

    public function getExchangeType(): ExchangeType;

    public function getExchangeStatus(): ExchangeStatus;

    public function isAvailableForReservation(): bool;

    public function markAsReserved(): void;

    public function markAsCompleted(): void;

    public function markAsArchived(): void;

    public function markAsPublished(): void;

    /**
     * Libellé humainement lisible utilisé dans les notifications
     * (ex: "l'objet « Perceuse »" ou "la compétence « Guitare »").
     */
    public function displayLabel(): string;
}
