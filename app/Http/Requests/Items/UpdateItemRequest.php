<?php

namespace App\Http\Requests\Items;

use App\Enums\CategoryType;
use App\Enums\ExchangeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                Rule::exists('categories', 'id')->where('type', CategoryType::Item->value),
            ],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'type' => ['sometimes', Rule::enum(ExchangeType::class)],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
