<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

/**
 * Autorisation fine sur un Item précis (contrairement au middleware
 * `admin`, qui ne fait qu'une vérification de rôle globale sur les
 * routes /api/admin/*).
 *
 * Volontairement pas de `Gate::before` global pour le rôle admin (voir
 * AppServiceProvider) : ici, un admin peut modérer n'importe quel item,
 * donc le contournement est explicite avec `$user->isAdmin()`.
 */
class ItemPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Item $item): bool
    {
        return $user->id === $item->user_id || $user->isAdmin();
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->id === $item->user_id || $user->isAdmin();
    }

    /**
     * Publier/archiver une annonce.
     */
    public function manageLifecycle(User $user, Item $item): bool
    {
        return $user->id === $item->user_id || $user->isAdmin();
    }
}
