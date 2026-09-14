<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // L'email n'est visible que par l'intéressé ou un administrateur :
            // on ne l'expose jamais dans les listings publics.
            'email' => $this->when(
                $request->user()?->is($this->resource) || $request->user()?->isAdmin(),
                $this->email,
            ),
            'role' => $this->when($request->user()?->isAdmin(), $this->role->value),
            'is_active' => $this->when($request->user()?->isAdmin(), (bool) $this->is_active),
            'member_since' => $this->created_at?->toDateString(),
        ];
    }
}
