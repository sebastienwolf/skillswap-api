<?php

namespace App\Models;

use App\Contracts\Exchangeable;
use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Demande de réservation d'un membre (`requester`) sur un Item ou un Skill
 * (`reservable`, relation polymorphique) appartenant à un autre membre.
 *
 * @use HasFactory<ReservationFactory>
 */
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'reservable_type',
        'reservable_id',
        'status',
        'message',
        'scheduled_at',
    ];

    // Cf. commentaire dans App\Models\User : syntaxe propriété plutôt que
    // la méthode `casts()`, pour une inférence de type fiable par Larastan.
    protected $casts = [
        'status' => ReservationStatus::class,
        'scheduled_at' => 'datetime',
    ];

    // Cf. commentaire dans App\Models\Item : valeur exprimée au format brut.
    protected $attributes = [
        'status' => ReservationStatus::Pending->value,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * L'Item ou le Skill concerné par cette réservation. Typé via le
     * contrat Exchangeable (plutôt que Model brut) pour que l'analyse
     * statique connaisse les méthodes du cycle de vie (markAsReserved,
     * reservations, ...) accessibles sur cette relation polymorphique.
     *
     * @return MorphTo<Exchangeable&Model, $this>
     */
    public function reservable(): MorphTo
    {
        // `morphTo()` ne peut pas connaître statiquement la classe cible
        // (Item ou Skill) : on précise le type via ce commentaire pour que
        // l'analyse statique le sache, conformément au type déclaré ci-dessus.
        /** @var MorphTo<Exchangeable&Model, $this> $relation */
        $relation = $this->morphTo();

        return $relation;
    }

    public function isPending(): bool
    {
        return $this->status === ReservationStatus::Pending;
    }
}
