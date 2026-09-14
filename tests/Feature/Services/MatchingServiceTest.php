<?php

namespace Tests\Feature\Services;

use App\Enums\ExchangeType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_owners_of_opposite_type_in_the_same_category(): void
    {
        $category = Category::factory()->forItems()->create();

        $offer = Item::factory()->for($category)->create(['type' => ExchangeType::Offer]);
        $matchingNeed = Item::factory()->for($category)->create(['type' => ExchangeType::Need]);

        $owners = (new MatchingService)->findOwnersToNotify($offer);

        $this->assertTrue($owners->contains('id', $matchingNeed->user_id));
    }

    #[Test]
    public function it_ignores_a_different_category(): void
    {
        $categoryA = Category::factory()->forItems()->create();
        $categoryB = Category::factory()->forItems()->create();

        $offer = Item::factory()->for($categoryA)->create(['type' => ExchangeType::Offer]);
        Item::factory()->for($categoryB)->create(['type' => ExchangeType::Need]);

        $owners = (new MatchingService)->findOwnersToNotify($offer);

        $this->assertCount(0, $owners);
    }

    #[Test]
    public function it_never_matches_the_owner_with_themselves(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->forItems()->create();

        $offer = Item::factory()->for($owner, 'owner')->for($category)->create(['type' => ExchangeType::Offer]);
        Item::factory()->for($owner, 'owner')->for($category)->create(['type' => ExchangeType::Need]);

        $owners = (new MatchingService)->findOwnersToNotify($offer);

        $this->assertCount(0, $owners);
    }

    #[Test]
    public function items_never_match_with_skills(): void
    {
        $category = Category::factory()->forItems()->create();
        $offer = Item::factory()->for($category)->create(['type' => ExchangeType::Offer]);

        // Une compétence, même dans une catégorie de même id, ne doit
        // jamais apparaître dans les correspondances d'un Item.
        Skill::factory()->create(['type' => ExchangeType::Need]);

        $owners = (new MatchingService)->findOwnersToNotify($offer);

        $this->assertCount(0, $owners);
    }
}
