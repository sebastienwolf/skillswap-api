<?php

namespace App\Enums;

/**
 * Rôle applicatif d'un utilisateur.
 *
 * Volontairement minimaliste (deux rôles) : un système de permissions plus
 * fin (type spatie/laravel-permission) serait de la sur-ingénierie tant que
 * le besoin réel ne le justifie pas (principe YAGNI).
 */
enum UserRole: string
{
    case Member = 'member';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Membre',
            self::Admin => 'Administrateur',
        };
    }
}
