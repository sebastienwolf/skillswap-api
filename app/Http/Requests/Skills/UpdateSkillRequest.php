<?php

namespace App\Http\Requests\Skills;

use App\Enums\CategoryType;
use App\Enums\ExchangeType;
use App\Enums\SkillLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('skill')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                Rule::exists('categories', 'id')->where('type', CategoryType::Skill->value),
            ],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'type' => ['sometimes', Rule::enum(ExchangeType::class)],
            'level' => ['sometimes', Rule::enum(SkillLevel::class)],
        ];
    }
}
