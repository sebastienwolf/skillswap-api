<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // Modifiables uniquement via UserController::update (Admin), qui est
        // lui-même protégé par UserPolicy::update (admin uniquement, jamais
        // sur soi-même) : aucun risque d'élévation de privilèges côté
        // inscription publique, qui ne renseigne jamais ces deux champs.
        'role',
        'is_active',
    ];

    /**
     * Valeurs par défaut alignées sur celles de la migration : sans cela,
     * un modèle fraîchement créé en mémoire (avant tout rechargement depuis
     * la base) exposerait `role` à `null` tant que la valeur par défaut SQL
     * n'a pas été relue, ce qui casse le cast en enum dans UserResource.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::Member->value,
        'is_active' => true,
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * On utilise ici la syntaxe historique (propriété) plutôt que la méthode
     * `casts()` introduite par Laravel 11 : Larastan n'infère pas encore de
     * façon fiable le type des enums castés via cette dernière, ce qui fait
     * apparaître de faux positifs en analyse statique (ex: `$user->role`
     * resterait typé `string` au lieu de `UserRole`).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * @return HasMany<Skill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * Réservations que cet utilisateur a lui-même demandées.
     *
     * @return HasMany<Reservation, $this>
     */
    public function reservationsMade(): HasMany
    {
        return $this->hasMany(Reservation::class, 'requester_id');
    }
}
