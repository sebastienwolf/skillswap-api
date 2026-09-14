<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reservation $reservation,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_requested',
            'reservation_id' => $this->reservation->id,
            'message' => sprintf(
                '%s souhaite réserver %s.',
                $this->reservation->requester->name,
                $this->reservation->reservable->displayLabel(),
            ),
        ];
    }
}
