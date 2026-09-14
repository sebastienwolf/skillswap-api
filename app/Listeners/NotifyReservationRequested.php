<?php

namespace App\Listeners;

use App\Events\ReservationRequested;
use App\Notifications\ReservationRequestedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyReservationRequested implements ShouldQueue
{
    public function handle(ReservationRequested $event): void
    {
        $event->reservation->reservable->ownerUser()
            ->notify(new ReservationRequestedNotification($event->reservation));
    }
}
