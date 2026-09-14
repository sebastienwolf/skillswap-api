<?php

namespace App\Http\Resources;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Skill
 */
class SkillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'level' => $this->level->value,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
