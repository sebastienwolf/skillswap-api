<?php

namespace Database\Seeders;

use App\Actions\Reservations\RequestReservationAction;
use App\Enums\ExchangeType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Jeu de données de démonstration : un administrateur, quelques membres,
 * des catégories, des annonces des deux types, et une réservation en
 * cours — de quoi explorer immédiatement l'API après un `migrate --seed`.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(RequestReservationAction $requestReservation): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin SkillSwap',
            'email' => 'admin@skillswap.test',
        ]);

        $members = User::factory()->count(6)->create();

        $itemCategories = Category::factory()->forItems()->count(4)->create();
        $skillCategories = Category::factory()->forSkills()->count(4)->create();

        $items = Item::factory()
            ->count(20)
            ->recycle($members)
            ->recycle($itemCategories)
            ->create();

        $skills = Skill::factory()
            ->count(20)
            ->recycle($members)
            ->recycle($skillCategories)
            ->create();

        // Une réservation "vivante" pour pouvoir tester tout de suite le
        // flux accept/decline/complete depuis Postman ou les tests manuels.
        $availableItem = $items->firstWhere('type', ExchangeType::Offer);
        $requester = $members->firstWhere('id', '!=', $availableItem?->user_id);

        if ($availableItem && $requester) {
            $requestReservation($availableItem, $requester, 'Toujours disponible ?');
        }

        $this->command?->info("Créé : 1 admin ({$admin->email} / password), {$members->count()} membres, {$items->count()} objets, {$skills->count()} compétences.");
    }
}
