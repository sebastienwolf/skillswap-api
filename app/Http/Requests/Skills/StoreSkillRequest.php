<?php

namespace App\Http\Requests\Skills;

use App\Enums\CategoryType;
use App\Enums\ExchangeType;
use App\Enums\SkillLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Skill::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('type', CategoryType::Skill->value),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(ExchangeType::class)],
            'level' => ['sometimes', Rule::enum(SkillLevel::class)],
        ];
    }
}
