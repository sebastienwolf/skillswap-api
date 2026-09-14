<?php

namespace App\Services;

use App\Contracts\Exchangeable;
use App\Enums\ExchangeStatus;
use App\Models\User;
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
        // Requête via des `where()` classiques plutôt que les scopes de
        // ExchangeableQueryBuilder (published(), byCategory(), ...) : ce
        // service ne connaît volontairement que le contrat Exchangeable,
        // pas le query builder personnalisé d'Item/Skill.
        return $exchangeable->newQuery()
            ->where('status', ExchangeStatus::Published)
            ->where('type', $exchangeable->getExchangeType()->opposite())
            ->where('category_id', $exchangeable->categoryId())
            ->where('user_id', '!=', $exchangeable->ownerId())
            ->with('owner')
            ->get();
    }

    /**
     * Propriétaires distincts à notifier pour une nouvelle annonce publiée.
     *
     * @return SupportCollection<int, User>
     */
    public function findOwnersToNotify(Exchangeable&Model $exchangeable): SupportCollection
    {
        return $this->findMatchesFor($exchangeable)
            ->map(fn (Exchangeable&Model $match) => $match->ownerUser())
            ->filter()
            ->unique('id')
            ->values();
    }
}
