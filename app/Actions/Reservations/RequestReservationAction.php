<?php

namespace App\Actions\Reservations;

use App\Contracts\Exchangeable;
use App\Events\ReservationRequested;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Concentre les règles métier de la création d'une réservation :
 * un contrôleur plus mince, une règle testée une seule fois.
 */
class RequestReservationAction
{
    public function __invoke(Exchangeable&Model $exchangeable, User $requester, ?string $message = null): Reservation
    {
        if ($exchangeable->user_id === $requester->id) {
            throw ValidationException::withMessages([
                'reservable' => 'Vous ne pouvez pas réserver votre propre annonce.',
            ]);
        }

        if (! $exchangeable->isAvailableForReservation()) {
            throw ValidationException::withMessages([
                'reservable' => 'Cette annonce n\'est plus disponible à la réservation.',
            ]);
        }

        /** @var Reservation $reservation */
        $reservation = $exchangeable->reservations()->create([
            'requester_id' => $requester->id,
            'message' => $message,
        ]);

        ReservationRequested::dispatch($reservation);

        return $reservation;
    }
}
