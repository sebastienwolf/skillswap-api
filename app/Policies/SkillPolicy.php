<?php

namespace App\Policies;

use App\Models\Skill;
use App\Models\User;

/**
 * Symétrique de ItemPolicy pour les compétences. La duplication entre les
 * deux classes reste volontairement explicite : les règles d'autorisation
 * sont plus lisibles et plus sûres à faire évoluer indépendamment que
 * factorisées derrière une abstraction commune prématurée.
 */
class SkillPolicy
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

    public function update(User $user, Skill $skill): bool
    {
        return $user->id === $skill->user_id || $user->isAdmin();
    }

    public function delete(User $user, Skill $skill): bool
    {
        return $user->id === $skill->user_id || $user->isAdmin();
    }

    public function manageLifecycle(User $user, Skill $skill): bool
    {
        return $user->id === $skill->user_id || $user->isAdmin();
    }
}
