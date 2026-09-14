<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(CategoryType::cases()),
        ];
    }

    public function forItems(): static
    {
        return $this->state(fn (array $attributes) => ['type' => CategoryType::Item]);
    }

    public function forSkills(): static
    {
        return $this->state(fn (array $attributes) => ['type' => CategoryType::Skill]);
    }
}
