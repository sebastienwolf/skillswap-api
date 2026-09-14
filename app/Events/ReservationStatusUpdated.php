<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Levé après chaque transition de statut d'une réservation (acceptée,
 * refusée, annulée ou terminée). Le listener consulte `$reservation->status`
 * pour adapter le message plutôt que de multiplier un événement par statut.
 */
class ReservationStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
    ) {}
}
