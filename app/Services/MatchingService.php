<?php

namespace App\Services;

use App\Contracts\Exchangeable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Recherche les correspondances potentielles pour une annonce donnée.
 *
 * Le point clé : ce service ne connaît ni Item ni Skill. Il s'appuie
 * uniquement sur le contrat Exchangeable et sur `newQuery()` (fourni par
 * Eloquent\Model) pour rester exécutable sur n'importe quel type de
 * contenu échangeable actuel ou futur (principe ouvert/fermé de SOLID).
 */
class MatchingService
{
    /**
     * Annonces "opposées" à celle fournie (une offre pour un besoin,
     * et inversement), dans la même catégorie, publiées, appartenant à
     * d'autres membres.
     *
     * @return Collection<int, Exchangeable&Model>
     */
    public function findMatchesFor(Exchangeable&Model $exchangeable): Collection
    {
        return $exchangeable->newQuery()
            ->published()
            ->ofType($exchangeable->getExchangeType()->opposite())
            ->byCategory($exchangeable->category_id)
            ->notOwnedBy($exchangeable->user_id)
            ->with('owner')
            ->get();
    }

    /**
     * Propriétaires distincts à notifier pour une nouvelle annonce publiée.
     *
     * @return SupportCollection<int, \App\Models\User>
     */
    public function findOwnersToNotify(Exchangeable&Model $exchangeable): SupportCollection
    {
        return $this->findMatchesFor($exchangeable)
            ->map(fn (Exchangeable&Model $match) => $match->owner)
            ->filter()
            ->unique('id')
            ->values();
    }
}
