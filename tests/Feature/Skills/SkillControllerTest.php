<?php

namespace Tests\Feature\Skills;

use App\Enums\ExchangeStatus;
use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SkillControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anyone_can_list_published_skills(): void
    {
        Skill::factory()->count(2)->create(['status' => ExchangeStatus::Published]);

        $this->getJson('/api/skills')->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function a_member_can_publish_a_skill_with_a_level(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forSkills()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/skills', [
            'category_id' => $category->id,
            'title' => 'Cours de guitare',
            'description' => 'Bases de la guitare classique.',
            'type' => 'offer',
            'level' => 'intermediate',
        ]);

        $response->assertCreated()->assertJsonPath('data.level', 'intermediate');
    }

    #[Test]
    public function an_invalid_level_is_rejected(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forSkills()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/skills', [
            'category_id' => $category->id,
            'title' => 'Cours de guitare',
            'description' => 'Bases.',
            'type' => 'offer',
            'level' => 'grand-maitre',
        ])->assertUnprocessable()->assertJsonValidationErrors('level');
    }

    #[Test]
    public function only_the_owner_can_delete_their_skill(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $skill = Skill::factory()->for($owner, 'owner')->create();

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/skills/{$skill->id}")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/skills/{$skill->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }
}
