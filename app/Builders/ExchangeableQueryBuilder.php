<?php

namespace App\Builders;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query builder Eloquent personnalisé, partagé par Item et Skill
 * (voir `newEloquentBuilder()` sur chaque modèle).
 *
 * Centraliser ces scopes ici évite de dupliquer les mêmes conditions
 * `where` dans les deux modèles, et rend les requêtes des contrôleurs
 * lisibles : `Item::query()->published()->byCategory($id)->paginate()`.
 *
 * Générique sur le modèle concret (TModelClass) plutôt que fixé sur
 * `Model` : sans ça, `Item::query()->get()` perdrait son type précis pour
 * l'analyse statique et remonterait une simple Collection<Model>.
 *
 * @template TModelClass of Model
 *
 * @extends Builder<TModelClass>
 */
class ExchangeableQueryBuilder extends Builder
{
    /**
     * @return self<TModelClass>
     */
    public function published(): self
    {
        return $this->where('status', ExchangeStatus::Published);
    }

    /**
     * @return self<TModelClass>
     */
    public function ofType(ExchangeType $type): self
    {
        return $this->where('type', $type);
    }

    /**
     * @return self<TModelClass>
     */
    public function byCategory(int $categoryId): self
    {
        return $this->where('category_id', $categoryId);
    }

    /**
     * @return self<TModelClass>
     */
    public function ownedBy(int $userId): self
    {
        return $this->where('user_id', $userId);
    }

    /**
     * @return self<TModelClass>
     */
    public function notOwnedBy(int $userId): self
    {
        return $this->where('user_id', '!=', $userId);
    }

    /**
     * @return self<TModelClass>
     */
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
