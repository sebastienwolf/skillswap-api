<?php

namespace App\Enums;

/**
 * Cycle de vie commun aux entités échangeables (Item, Skill) :
 *
 *   Published --> Reserved --> Completed
 *       \-----------------------> Archived
 *
 * Les transitions autorisées sont centralisées dans
 * App\Models\Concerns\HasExchangeLifecycle plutôt que dupliquées
 * dans chaque modèle.
 */
enum ExchangeStatus: string
{
    case Published = 'published';
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Archived = 'archived';
}
