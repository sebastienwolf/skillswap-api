<?php

namespace Database\Factories;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->forItems(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(ExchangeType::cases()),
            'status' => ExchangeStatus::Published,
            'quantity' => fake()->numberBetween(1, 5),
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

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ExchangeStatus::Archived]);
    }
}
