<?php

namespace App\Listeners;

use App\Events\ReservationStatusUpdated;
use App\Notifications\ReservationStatusUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyReservationStatusUpdated implements ShouldQueue
{
    public function handle(ReservationStatusUpdated $event): void
    {
        $event->reservation->requester
            ->notify(new ReservationStatusUpdatedNotification($event->reservation));
    }
}
