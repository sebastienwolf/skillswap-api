<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationStatusUpdatedNotification extends Notification
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
            'type' => 'reservation_status_updated',
            'reservation_id' => $this->reservation->id,
            'status' => $this->reservation->status->value,
            'message' => sprintf(
                'Votre demande concernant %s est maintenant : %s.',
                $this->reservation->reservable->displayLabel(),
                $this->reservation->status->value,
            ),
        ];
    }
}
