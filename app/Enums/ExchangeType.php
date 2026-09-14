<?php

namespace App\Enums;

/**
 * Sens d'une annonce d'échange : une offre (je propose) ou un besoin
 * (je recherche). Utilisé aussi bien par les objets (Item) que par les
 * compétences (Skill), d'où sa place dans les enums partagés.
 */
enum ExchangeType: string
{
    case Offer = 'offer';
    case Need = 'need';

    /**
     * Le type qui correspondrait à celui-ci dans un appariement
     * (une offre "matche" avec un besoin, et inversement).
     */
    public function opposite(): self
    {
        return match ($this) {
            self::Offer => self::Need,
            self::Need => self::Offer,
        };
    }
}
