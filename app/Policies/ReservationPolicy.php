<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Seuls le demandeur, le propriétaire de la ressource réservée, et un
     * administrateur peuvent consulter le détail d'une réservation.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->requester_id
            || $user->id === $reservation->reservable->ownerId()
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Accepter ou refuser une demande : réservé au propriétaire de la
     * ressource concernée (pas au demandeur lui-même), ou à un admin.
     */
    public function respond(User $user, Reservation $reservation): bool
    {
        return $reservation->isPending()
            && ($user->id === $reservation->reservable->ownerId() || $user->isAdmin());
    }

    /**
     * Annuler : le demandeur peut se rétracter, le propriétaire peut
     * annuler une réservation déjà acceptée.
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->requester_id
            || $user->id === $reservation->reservable->ownerId()
            || $user->isAdmin();
    }

    /**
     * Clore l'échange : réservé au propriétaire, une fois la demande acceptée.
     */
    public function complete(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->reservable->ownerId() || $user->isAdmin();
    }
}
