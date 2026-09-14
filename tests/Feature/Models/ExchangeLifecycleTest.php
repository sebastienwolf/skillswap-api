<?php

namespace Tests\Feature\Models;

use App\Enums\ExchangeStatus;
use App\Models\Item;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Le trait HasExchangeLifecycle est partagé par Item et Skill : on le
 * teste une seule fois via Item, puis on vérifie juste que Skill en
 * bénéficie aussi (DRY dans les tests également).
 */
class ExchangeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_from_published_to_reserved_to_completed(): void
    {
        $item = Item::factory()->create(['status' => ExchangeStatus::Published]);

        $item->markAsReserved();
        $this->assertSame(ExchangeStatus::Reserved, $item->fresh()->status);

        $item->markAsCompleted();
        $this->assertSame(ExchangeStatus::Completed, $item->fresh()->status);
    }

    #[Test]
    public function it_refuses_to_complete_an_item_that_is_not_reserved(): void
    {
        $item = Item::factory()->create(['status' => ExchangeStatus::Published]);

        $this->expectException(RuntimeException::class);

        $item->markAsCompleted();
    }

    #[Test]
    public function archiving_is_allowed_from_published_or_reserved(): void
    {
        $published = Item::factory()->create(['status' => ExchangeStatus::Published]);
        $published->markAsArchived();
        $this->assertSame(ExchangeStatus::Archived, $published->fresh()->status);

        $reserved = Item::factory()->create(['status' => ExchangeStatus::Reserved]);
        $reserved->markAsArchived();
        $this->assertSame(ExchangeStatus::Archived, $reserved->fresh()->status);
    }

    #[Test]
    public function a_cancelled_reservation_makes_the_resource_available_again(): void
    {
        $reserved = Item::factory()->create(['status' => ExchangeStatus::Reserved]);

        $reserved->markAsPublished();

        $this->assertSame(ExchangeStatus::Published, $reserved->fresh()->status);
    }

    #[Test]
    public function skills_share_the_exact_same_lifecycle_rules(): void
    {
        $skill = Skill::factory()->create(['status' => ExchangeStatus::Published]);

        $skill->markAsReserved();

        $this->assertSame(ExchangeStatus::Reserved, $skill->fresh()->status);
    }
}
