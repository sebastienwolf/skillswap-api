<?php

namespace App\Enums;

/**
 * Cycle de vie d'une demande de réservation entre deux membres.
 *
 *   Pending --> Accepted --> Completed
 *      |            \----------> Cancelled
 *      \--> Declined
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
