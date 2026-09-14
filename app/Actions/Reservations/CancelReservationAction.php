<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Events\ReservationStatusUpdated;
use App\Models\Reservation;

class CancelReservationAction
{
    public function __invoke(Reservation $reservation): Reservation
    {
        $wasAccepted = $reservation->status === ReservationStatus::Accepted;

        $reservation->status = ReservationStatus::Cancelled;
        $reservation->save();

        if ($wasAccepted) {
            // La ressource était verrouillée pour cette réservation : elle
            // redevient disponible pour d'autres demandes.
            $reservation->reservable->markAsPublished();
        }

        ReservationStatusUpdated::dispatch($reservation);

        return $reservation;
    }
}
