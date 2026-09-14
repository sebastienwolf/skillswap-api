<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_admin_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/categories', [
            'name' => 'Bricolage',
            'type' => 'item',
        ]);

        $response->assertCreated()->assertJsonPath('data.slug', 'bricolage');
    }

    #[Test]
    public function a_regular_member_cannot_manage_categories(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')
            ->postJson('/api/admin/categories', ['name' => 'Bricolage', 'type' => 'item'])
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_can_update_and_delete_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->forItems()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/categories/{$category->id}", ['name' => 'Nouveau nom'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau nom');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
