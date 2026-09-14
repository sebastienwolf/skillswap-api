<?php

namespace App\Builders;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder Eloquent personnalisé, partagé par Item et Skill
 * (voir `newEloquentBuilder()` sur chaque modèle).
 *
 * Centraliser ces scopes ici évite de dupliquer les mêmes conditions
 * `where` dans les deux modèles, et rend les requêtes des contrôleurs
 * lisibles : `Item::query()->published()->byCategory($id)->paginate()`.
 *
 * @extends Builder<\Illuminate\Database\Eloquent\Model>
 */
class ExchangeableQueryBuilder extends Builder
{
    public function published(): self
    {
        return $this->where('status', ExchangeStatus::Published);
    }

    public function ofType(ExchangeType $type): self
    {
        return $this->where('type', $type);
    }

    public function byCategory(int $categoryId): self
    {
        return $this->where('category_id', $categoryId);
    }

    public function ownedBy(int $userId): self
    {
        return $this->where('user_id', $userId);
    }

    public function notOwnedBy(int $userId): self
    {
        return $this->where('user_id', '!=', $userId);
    }

    public function search(?string $term): self
    {
        if (blank($term)) {
            return $this;
        }

        return $this->where(function (self $query) use ($term): void {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
