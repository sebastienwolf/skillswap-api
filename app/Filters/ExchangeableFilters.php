<?php

namespace App\Filters;

use App\Builders\ExchangeableQueryBuilder;
use App\Enums\ExchangeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Centralise les filtres de listing partagés par Item et Skill (mine/type/
 * category_id), auparavant dupliqués à l'identique dans ItemController et
 * SkillController. Une seule classe plutôt qu'une par modèle : Item et
 * Skill partagent déjà le même contrat (App\Contracts\Exchangeable) et le
 * même query builder, dupliquer la classe aurait juste déplacé le problème.
 *
 * Le constructeur ne reçoit que des valeurs déjà extraites (pas l'objet
 * Request) : la logique de filtrage reste ainsi testable sans monter de
 * requête HTTP. `fromRequest()` fait ce travail d'extraction pour l'usage
 * courant depuis un contrôleur.
 */
class ExchangeableFilters
{
    public function __construct(
        private readonly bool $mine,
        private readonly ?int $currentUserId,
        private readonly ?string $type,
        private readonly ?int $categoryId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // `query()` peut renvoyer un tableau (ex: `?type[]=x`) : on ne garde
        // les valeurs que si elles sont bien scalaires, plutôt que de risquer
        // un TypeError sur `ExchangeType::from()` ou un `(int)` silencieux
        // et absurde sur un tableau.
        $type = $request->query('type');
        $categoryId = $request->query('category_id');

        return new self(
            mine: $request->boolean('mine'),
            currentUserId: $request->user()?->id,
            type: is_string($type) ? $type : null,
            categoryId: is_numeric($categoryId) ? (int) $categoryId : null,
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  ExchangeableQueryBuilder<TModel>  $query
     *
     * @return ExchangeableQueryBuilder<TModel>
     */
    public function apply(ExchangeableQueryBuilder $query): ExchangeableQueryBuilder
    {
        if ($this->mine && $this->currentUserId !== null) {
            $query->ownedBy($this->currentUserId);
        } else {
            $query->published();
        }

        if ($this->type !== null) {
            $query->ofType(ExchangeType::from($this->type));
        }

        if ($this->categoryId !== null) {
            $query->byCategory($this->categoryId);
        }

        return $query;
    }
}
