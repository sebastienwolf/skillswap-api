<?php

namespace Tests\Feature\Items;

use App\Enums\ExchangeStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ItemControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anyone_can_list_published_items(): void
    {
        Item::factory()->count(3)->create(['status' => ExchangeStatus::Published]);
        Item::factory()->create(['status' => ExchangeStatus::Archived]);

        $response = $this->getJson('/api/items');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    #[Test]
    public function a_member_can_publish_a_new_item(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forItems()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/items', [
            'category_id' => $category->id,
            'title' => 'Perceuse Bosch',
            'description' => 'Prêtée pour le week-end.',
            'type' => 'offer',
            'quantity' => 1,
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Perceuse Bosch');
        $this->assertDatabaseHas('items', ['title' => 'Perceuse Bosch', 'user_id' => $user->id]);
    }

    #[Test]
    public function a_guest_cannot_create_an_item(): void
    {
        $category = Category::factory()->forItems()->create();

        $this->postJson('/api/items', [
            'category_id' => $category->id,
            'title' => 'Perceuse',
            'description' => 'Test',
            'type' => 'offer',
        ])->assertUnauthorized();
    }

    #[Test]
    public function only_the_owner_can_update_their_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = Item::factory()->for($owner)->create();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/items/{$item->id}", ['title' => 'Piraté'])
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/items/{$item->id}", ['title' => 'Modifié'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Modifié');
    }

    #[Test]
    public function only_the_owner_can_archive_their_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = Item::factory()->for($owner)->create(['status' => ExchangeStatus::Published]);

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/items/{$item->id}/archive")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/items/{$item->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', ExchangeStatus::Archived->value);
    }

    #[Test]
    public function a_member_can_filter_their_own_items_including_drafts(): void
    {
        $user = User::factory()->create();
        Item::factory()->for($user)->create(['status' => ExchangeStatus::Archived]);
        Item::factory()->create(['status' => ExchangeStatus::Published]); // un autre membre

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/items?mine=1');

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
