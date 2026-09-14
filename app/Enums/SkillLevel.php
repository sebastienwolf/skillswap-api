<?php

namespace App\Enums;

/**
 * Niveau de maîtrise d'une compétence, propre au modèle Skill
 * (un Item n'a pas de niveau, d'où un enum dédié plutôt qu'un champ
 * générique partagé qui n'aurait pas de sens pour les objets).
 */
enum SkillLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Expert = 'expert';
}
