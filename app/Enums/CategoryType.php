<?php

namespace App\Enums;

/**
 * Une catégorie s'applique soit aux objets, soit aux compétences.
 * Une seule table `categories` avec un discriminant `type` évite de
 * dupliquer la même structure dans deux tables (principe DRY).
 */
enum CategoryType: string
{
    case Item = 'item';
    case Skill = 'skill';
}
