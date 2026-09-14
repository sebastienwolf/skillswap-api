<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Reservation
 */
class ReservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'message' => $this->message,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'requester' => new UserResource($this->whenLoaded('requester')),
            'reservable_type' => $this->reservable instanceof Item ? 'item' : 'skill',
            'reservable' => $this->whenLoaded(
                'reservable',
                fn () => $this->reservable instanceof Item
                    ? new ItemResource($this->reservable)
                    : new SkillResource($this->reservable),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
