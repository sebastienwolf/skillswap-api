<?php

namespace App\Services;

use App\Enums\ExchangeStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agrège les indicateurs affichés sur le tableau de bord administrateur
 * (Admin\DashboardController). Isoler ces requêtes dans un service dédié
 * évite d'alourdir le contrôleur et les rend testables indépendamment.
 */
class ExchangeStatsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'users' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
            ],
            'items' => $this->countByStatus(Item::query()),
            'skills' => $this->countByStatus(Skill::query()),
            'reservations' => [
                'pending' => Reservation::query()->where('status', 'pending')->count(),
                'accepted' => Reservation::query()->where('status', 'accepted')->count(),
                'completed' => Reservation::query()->where('status', 'completed')->count(),
            ],
            'top_categories' => $this->topCategories(),
        ];
    }

    /**
     * Générique sur TModel : accepte aussi bien `Item::query()` que
     * `Skill::query()`, sans avoir à connaître leur query builder
     * personnalisé (le générique Builder<TModel> de base suffit ici,
     * on ne se sert d'aucun scope propre à ExchangeableQueryBuilder).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     *
     * @return array<string, int>
     */
    private function countByStatus($query): array
    {
        $counts = (clone $query)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(ExchangeStatus::cases())
            ->mapWithKeys(fn (ExchangeStatus $status) => [
                $status->value => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, items_count: int, skills_count: int}>
     */
    private function topCategories(): array
    {
        return Category::query()
            ->withCount(['items', 'skills'])
            ->get()
            ->sortByDesc(fn (Category $category) => $category->items_count + $category->skills_count)
            ->take(5)
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'items_count' => $category->items_count,
                'skills_count' => $category->skills_count,
            ])
            ->values()
            ->all();
    }
}
