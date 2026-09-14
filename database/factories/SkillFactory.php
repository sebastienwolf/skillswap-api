<?php

namespace Database\Factories;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Enums\SkillLevel;
use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->forSkills(),
            'title' => ucfirst(fake()->words(2, true)),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(ExchangeType::cases()),
            'status' => ExchangeStatus::Published,
            'level' => fake()->randomElement(SkillLevel::cases()),
        ];
    }

    public function offer(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ExchangeType::Offer]);
    }

    public function need(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ExchangeType::Need]);
    }
}
