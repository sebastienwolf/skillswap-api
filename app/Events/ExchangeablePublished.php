<?php

namespace App\Events;

use App\Contracts\Exchangeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Levé quand un Item OU un Skill devient "publié" (à la création, ou en
 * sortant du statut archivé). Un seul événement pour les deux types de
 * contenu : le comportement qui en découle (chercher des correspondances)
 * ne dépend pas de la classe concrète, seulement du contrat Exchangeable.
 */
class ExchangeablePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Exchangeable&Model $exchangeable,
    ) {}
}
