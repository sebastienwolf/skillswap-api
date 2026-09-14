<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Events\ReservationStatusUpdated;
use App\Models\Reservation;

class DeclineReservationAction
{
    public function __invoke(Reservation $reservation): Reservation
    {
        $reservation->status = ReservationStatus::Declined;
        $reservation->save();

        ReservationStatusUpdated::dispatch($reservation);

        return $reservation;
    }
}
