<?php

namespace App\Models;

use App\Builders\ExchangeableQueryBuilder;
use App\Contracts\Exchangeable;
use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Enums\SkillLevel;
use App\Models\Concerns\HasExchangeLifecycle;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Une compétence proposée (offer) ou recherchée (need) par un membre.
 *
 * @use HasFactory<SkillFactory>
 * @mixin ExchangeableQueryBuilder
 */
class Skill extends Model implements Exchangeable
{
    /** @use HasFactory<SkillFactory> */
    use HasExchangeLifecycle, HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'type',
        'status',
        'level',
    ];

    // Cf. commentaire dans App\Models\Item : syntaxe propriété plutôt que
    // la méthode `casts()`, pour une inférence de type fiable par Larastan.
    protected $casts = [
        'type' => ExchangeType::class,
        'status' => ExchangeStatus::class,
        'level' => SkillLevel::class,
    ];

    // Cf. commentaire dans App\Models\Item : valeurs exprimées au format brut.
    protected $attributes = [
        'status' => ExchangeStatus::Published->value,
        'level' => SkillLevel::Beginner->value,
    ];

    /**
     * @return ExchangeableQueryBuilder<static>
     */
    public function newEloquentBuilder($query): ExchangeableQueryBuilder
    {
        return new ExchangeableQueryBuilder($query);
    }

    /**
     * @return MorphMany<Reservation, $this>
     */
    public function reservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }

    public function displayLabel(): string
    {
        return sprintf('la compétence « %s »', $this->title);
    }
}
