<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Events\ReservationStatusUpdated;
use App\Models\Reservation;

class CompleteReservationAction
{
    public function __invoke(Reservation $reservation): Reservation
    {
        $reservation->reservable->markAsCompleted();

        $reservation->status = ReservationStatus::Completed;
        $reservation->save();

        ReservationStatusUpdated::dispatch($reservation);

        return $reservation;
    }
}
