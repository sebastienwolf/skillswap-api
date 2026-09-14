<?php

namespace App\Models;

use App\Builders\ExchangeableQueryBuilder;
use App\Contracts\Exchangeable;
use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Models\Concerns\HasExchangeLifecycle;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Un objet physique proposé (offer) ou recherché (need) par un membre.
 *
 * @use HasFactory<ItemFactory>
 * @mixin ExchangeableQueryBuilder<Item>
 */
class Item extends Model implements Exchangeable
{
    /** @use HasFactory<ItemFactory> */
    use HasExchangeLifecycle, HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'type',
        'status',
        'quantity',
    ];

    // Syntaxe propriété plutôt que la méthode `casts()` (Laravel 11) : voir
    // le commentaire équivalent dans App\Models\User pour la justification
    // (fiabilité de l'inférence de type des enums par Larastan).
    protected $casts = [
        'type' => ExchangeType::class,
        'status' => ExchangeStatus::class,
        'quantity' => 'integer',
    ];

    // Valeurs par défaut exprimées dans leur format "brut" de stockage
    // (et non l'instance d'enum) : ce tableau alimente directement les
    // attributs internes du modèle, en amont de la couche de cast.
    protected $attributes = [
        'status' => ExchangeStatus::Published->value,
        'quantity' => 1,
    ];

    /**
     * @return ExchangeableQueryBuilder<static>
     */
    public function newEloquentBuilder($query): ExchangeableQueryBuilder
    {
        /** @var ExchangeableQueryBuilder<static> $builder */
        $builder = new ExchangeableQueryBuilder($query);

        return $builder;
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
        return sprintf('l\'objet « %s »', $this->title);
    }
}
