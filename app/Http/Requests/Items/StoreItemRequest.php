<?php

namespace App\Http\Requests\Items;

use App\Enums\CategoryType;
use App\Enums\ExchangeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Item::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('type', CategoryType::Item->value),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(ExchangeType::class)],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
