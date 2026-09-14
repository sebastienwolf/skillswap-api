<?php

namespace App\Contracts;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contrat commun à toute entité pouvant être échangée entre membres
 * (un objet, une compétence, demain peut-être un service).
 *
 * Grâce à cette interface, App\Services\MatchingService ou les Actions de
 * réservation peuvent manipuler indifféremment un Item ou un Skill sans
 * connaître leur classe concrète (principe d'inversion de dépendance / D
 * de SOLID), et sans le moindre `instanceof` dispersé dans le code.
 */
interface Exchangeable
{
    public function owner(): BelongsTo;

    public function category(): BelongsTo;

    public function getExchangeType(): ExchangeType;

    public function getExchangeStatus(): ExchangeStatus;

    public function isAvailableForReservation(): bool;

    public function markAsReserved(): void;

    public function markAsCompleted(): void;

    public function markAsArchived(): void;

    /**
     * Libellé humainement lisible utilisé dans les notifications
     * (ex: "l'objet « Perceuse »" ou "la compétence « Guitare »").
     */
    public function displayLabel(): string;
}
