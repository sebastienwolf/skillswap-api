<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Events\ReservationStatusUpdated;
use App\Models\Reservation;

/**
 * Accepter une demande verrouille la ressource (elle passe "réservée") et
 * décline automatiquement les autres demandes en attente sur la même
 * ressource : elle ne peut être promise qu'à une seule personne à la fois.
 */
class AcceptReservationAction
{
    public function __invoke(Reservation $reservation): Reservation
    {
        $reservation->reservable->markAsReserved();

        $reservation->status = ReservationStatus::Accepted;
        $reservation->save();

        $this->declineOtherPendingRequests($reservation);

        ReservationStatusUpdated::dispatch($reservation);

        return $reservation;
    }

    private function declineOtherPendingRequests(Reservation $accepted): void
    {
        $accepted->reservable->reservations()
            ->where('id', '!=', $accepted->id)
            ->where('status', ReservationStatus::Pending)
            ->get()
            ->each(function (Reservation $reservation): void {
                $reservation->update(['status' => ReservationStatus::Declined]);
                ReservationStatusUpdated::dispatch($reservation);
            });
    }
}
