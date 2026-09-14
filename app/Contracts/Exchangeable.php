<?php

namespace App\Contracts;

use App\Builders\ExchangeableQueryBuilder;
use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\User;
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
 * Les annotations ci-dessous décrivent, pour l'analyse statique, les
 * colonnes et relations chargées dynamiquement par Eloquent (magic
 * properties) que tout Exchangeable expose réellement (voir Item/Skill).
 *
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int $category_id
 * @property-read User $owner
 * @property-read Category $category
 */
interface Exchangeable
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo;

    /**
     * Réservations reçues sur cette ressource (relation polymorphique).
     * Utilisé notamment par les Actions de réservation, qui manipulent
     * l'annonce concernée uniquement via ce contrat.
     *
     * @return MorphMany<Reservation, $this>
     */
    public function reservations(): MorphMany;

    public function getExchangeType(): ExchangeType;

    public function getExchangeStatus(): ExchangeStatus;

    public function isAvailableForReservation(): bool;

    public function markAsReserved(): void;

    public function markAsCompleted(): void;

    public function markAsArchived(): void;

    public function markAsPublished(): void;

    /**
     * Déclaré ici (et non simplement dans Item/Skill) pour que
     * App\Services\MatchingService, qui manipule uniquement le type
     * `Exchangeable&Model`, sache que les scopes de ExchangeableQueryBuilder
     * (published(), byCategory(), ...) sont disponibles sur `newQuery()`.
     *
     * @return ExchangeableQueryBuilder<static>
     */
    public function newEloquentBuilder($query): ExchangeableQueryBuilder;

    /**
     * Libellé humainement lisible utilisé dans les notifications
     * (ex: "l'objet « Perceuse »" ou "la compétence « Guitare »").
     */
    public function displayLabel(): string;
}
