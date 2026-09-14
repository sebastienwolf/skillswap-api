<?php

namespace Tests\Feature\Filters;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Filters\ExchangeableFilters;
use App\Models\Category;
use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Teste la classe de filtrage directement (sans passer par une route HTTP) :
 * c'est justement l'intérêt de l'avoir extraite des contrôleurs.
 */
class ExchangeableFiltersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_only_published_items_by_default(): void
    {
        Item::factory()->create(['status' => ExchangeStatus::Published]);
        Item::factory()->create(['status' => ExchangeStatus::Archived]);

        $filters = new ExchangeableFilters(mine: false, currentUserId: null, type: null, categoryId: null);

        $results = $filters->apply(Item::query())->get();

        $this->assertCount(1, $results);
        $this->assertSame(ExchangeStatus::Published, $results->first()->status);
    }

    #[Test]
    public function mine_without_a_current_user_falls_back_to_published_only(): void
    {
        Item::factory()->create(['status' => ExchangeStatus::Published]);
        Item::factory()->create(['status' => ExchangeStatus::Archived]);

        $filters = new ExchangeableFilters(mine: true, currentUserId: null, type: null, categoryId: null);

        $results = $filters->apply(Item::query())->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function mine_with_a_current_user_returns_all_their_items_regardless_of_status(): void
    {
        $user = User::factory()->create();
        Item::factory()->for($user, 'owner')->create(['status' => ExchangeStatus::Archived]);
        Item::factory()->for($user, 'owner')->create(['status' => ExchangeStatus::Published]);
        Item::factory()->create(['status' => ExchangeStatus::Published]); // un autre membre

        $filters = new ExchangeableFilters(mine: true, currentUserId: $user->id, type: null, categoryId: null);

        $results = $filters->apply(Item::query())->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_filters_by_type(): void
    {
        Item::factory()->create(['status' => ExchangeStatus::Published, 'type' => ExchangeType::Offer]);
        Item::factory()->create(['status' => ExchangeStatus::Published, 'type' => ExchangeType::Need]);

        $filters = new ExchangeableFilters(mine: false, currentUserId: null, type: 'need', categoryId: null);

        $results = $filters->apply(Item::query())->get();

        $this->assertCount(1, $results);
        $this->assertSame(ExchangeType::Need, $results->first()->type);
    }

    #[Test]
    public function it_filters_by_category(): void
    {
        $category = Category::factory()->forItems()->create();
        Item::factory()->for($category)->create(['status' => ExchangeStatus::Published]);
        Item::factory()->create(['status' => ExchangeStatus::Published]); // autre catégorie

        $filters = new ExchangeableFilters(mine: false, currentUserId: null, type: null, categoryId: $category->id);

        $results = $filters->apply(Item::query())->get();

        $this->assertCount(1, $results);
        $this->assertSame($category->id, $results->first()->category_id);
    }

    #[Test]
    public function it_combines_type_and_category_filters(): void
    {
        $category = Category::factory()->forItems()->create();
        Item::factory()->for($category)->create(['status' => ExchangeStatus::Published, 'type' => ExchangeType::Offer]);
        Item::factory()->for($category)->create(['status' => ExchangeStatus::Published, 'type' => ExchangeType::Need]);
        Item::factory()->create(['status' => ExchangeStatus::Published, 'type' => ExchangeType::Offer]); // autre catégorie

        $filters = new ExchangeableFilters(mine: false, currentUserId: null, type: 'offer', categoryId: $category->id);

        $results = $filters->apply(Item::query())->get();

        $this->assertCount(1, $results);
    }

    /**
     * Même classe, même comportement pour Skill : c'est précisément ce que
     * la mutualisation est censée garantir (voir App\Filters\ExchangeableFilters).
     */
    #[Test]
    public function it_applies_identically_to_skills(): void
    {
        $user = User::factory()->create();
        Skill::factory()->for($user, 'owner')->create(['status' => ExchangeStatus::Archived]);
        Skill::factory()->create(['status' => ExchangeStatus::Published]); // un autre membre

        $filters = new ExchangeableFilters(mine: true, currentUserId: $user->id, type: null, categoryId: null);

        $results = $filters->apply(Skill::query())->get();

        $this->assertCount(1, $results);
    }
}
