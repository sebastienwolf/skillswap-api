<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestion des comptes par un administrateur (voir Admin\UserController).
 *
 * Contrairement aux autres Policies, un administrateur n'est PAS
 * automatiquement autorisé sur toute action : il ne peut pas se modifier
 * ou se supprimer lui-même via ce canal, pour éviter de se retirer ses
 * propres droits par erreur (ou de se supprimer accidentellement).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->isNot($target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->isNot($target);
    }
}
