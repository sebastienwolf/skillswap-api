<?php

namespace App\Http\Requests\Reservations;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Reservation::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Le client précise sur quel type de ressource il réserve :
            // évite d'exposer les noms de classes PHP dans l'API publique.
            'reservable_type' => ['required', Rule::in(['item', 'skill'])],
            'reservable_id' => ['required', 'integer'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
