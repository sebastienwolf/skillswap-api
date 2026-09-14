<?php

namespace Tests\Feature\Services;

use App\Enums\ExchangeStatus;
use App\Models\Item;
use App\Services\ExchangeStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExchangeStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_items_by_status(): void
    {
        Item::factory()->count(2)->create(['status' => ExchangeStatus::Published]);
        Item::factory()->create(['status' => ExchangeStatus::Archived]);

        $summary = (new ExchangeStatsService)->summary();

        $this->assertSame(2, $summary['items'][ExchangeStatus::Published->value]);
        $this->assertSame(1, $summary['items'][ExchangeStatus::Archived->value]);
        $this->assertSame(0, $summary['items'][ExchangeStatus::Reserved->value]);
    }
}
