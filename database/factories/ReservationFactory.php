<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            // Une réservation cible un Item par défaut ; utiliser `for()`
            // pour cibler un Skill précis depuis un seeder ou un test.
            'reservable_type' => Item::class,
            'reservable_id' => Item::factory(),
            'status' => ReservationStatus::Pending,
            'message' => fake()->optional()->sentence(),
        ];
    }
}
