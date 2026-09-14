<?php

namespace Tests\Feature\Admin;

use App\Enums\ExchangeStatus;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_regular_member_cannot_access_the_admin_dashboard(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    #[Test]
    public function a_guest_cannot_access_the_admin_dashboard(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    #[Test]
    public function an_admin_can_view_the_dashboard_summary(): void
    {
        $admin = User::factory()->admin()->create();
        Item::factory()->count(2)->create(['status' => ExchangeStatus::Published]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/dashboard');

        $response->assertOk()->assertJsonStructure([
            'data' => ['users', 'items', 'skills', 'reservations', 'top_categories'],
        ]);
    }

    #[Test]
    public function an_admin_sees_every_item_regardless_of_status(): void
    {
        $admin = User::factory()->admin()->create();
        Item::factory()->create(['status' => ExchangeStatus::Published]);
        Item::factory()->create(['status' => ExchangeStatus::Archived]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/items')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function an_admin_can_deactivate_a_member_account(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$member->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => false]);
    }

    #[Test]
    public function an_admin_cannot_edit_their_own_account_through_this_endpoint(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$admin->id}", ['is_active' => false])
            ->assertForbidden();
    }
}
