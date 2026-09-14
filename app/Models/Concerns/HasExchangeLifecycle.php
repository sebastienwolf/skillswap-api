<?php

namespace App\Models\Concerns;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Implémentation partagée du cycle de vie décrit par App\Contracts\Exchangeable.
 *
 * Item et Skill utilisent ce même trait : la règle métier ("on ne peut
 * réserver que du contenu publié", "on ne peut terminer que du contenu
 * réservé", ...) n'existe qu'à un seul endroit (DRY). Chaque modèle garde
 * uniquement ce qui lui est propre (le niveau pour Skill, la quantité
 * pour Item).
 *
 * Pré-requis pour la classe qui utilise ce trait : une colonne `status`
 * castée en ExchangeStatus, une colonne `type` castée en ExchangeType,
 * une relation `user_id` vers User et `category_id` vers Category.
 */
trait HasExchangeLifecycle
{
    /**
     * Typé sur Model (et non $this) pour rester compatible avec la
     * signature déclarée par App\Contracts\Exchangeable::owner() : les
     * génériques de BelongsTo ne sont pas covariants, `$this` (Item ou
     * Skill) ne serait donc pas accepté là où l'interface attend Model.
     * `belongsTo()` infère cependant `$this` de lui-même : la variable
     * annotée ci-dessous force explicitement le type large attendu.
     *
     * @return BelongsTo<User, Model>
     */
    public function owner(): BelongsTo
    {
        /** @var BelongsTo<User, Model> $relation */
        $relation = $this->belongsTo(User::class, 'user_id');

        return $relation;
    }

    /**
     * @return BelongsTo<Category, Model>
     */
    public function category(): BelongsTo
    {
        /** @var BelongsTo<Category, Model> $relation */
        $relation = $this->belongsTo(Category::class);

        return $relation;
    }

    public function ownerId(): int
    {
        return $this->user_id;
    }

    public function categoryId(): int
    {
        return $this->category_id;
    }

    public function ownerUser(): User
    {
        return $this->owner;
    }

    public function getExchangeType(): ExchangeType
    {
        return $this->type;
    }

    public function getExchangeStatus(): ExchangeStatus
    {
        return $this->status;
    }

    public function isAvailableForReservation(): bool
    {
        return $this->status === ExchangeStatus::Published;
    }

    public function markAsReserved(): void
    {
        $this->transitionTo(ExchangeStatus::Reserved, from: [ExchangeStatus::Published]);
    }

    public function markAsCompleted(): void
    {
        $this->transitionTo(ExchangeStatus::Completed, from: [ExchangeStatus::Reserved]);
    }

    public function markAsArchived(): void
    {
        $this->transitionTo(ExchangeStatus::Archived, from: [ExchangeStatus::Published, ExchangeStatus::Reserved]);
    }

    public function markAsPublished(): void
    {
        // Depuis "archivé" (republication manuelle) ou "réservé" (la
        // réservation en cours vient d'être annulée : la ressource redevient
        // disponible).
        $this->transitionTo(ExchangeStatus::Published, from: [ExchangeStatus::Archived, ExchangeStatus::Reserved]);
    }

    /**
     * Applique une transition de statut si elle est autorisée, sinon lève
     * une exception explicite plutôt que de laisser l'état devenir
     * incohérent silencieusement.
     *
     * @param  array<int, ExchangeStatus>  $from
     */
    private function transitionTo(ExchangeStatus $to, array $from): void
    {
        if (! in_array($this->status, $from, strict: true)) {
            throw new RuntimeException(sprintf(
                'Transition invalide : impossible de passer de "%s" à "%s".',
                $this->status->value,
                $to->value,
            ));
        }

        $this->status = $to;
        $this->save();
    }
}
