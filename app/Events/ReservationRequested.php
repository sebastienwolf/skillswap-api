<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
    ) {}
}
