<?php

namespace Tests\Unit\Enums;

use App\Enums\ExchangeType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExchangeTypeTest extends TestCase
{
    #[Test]
    public function an_offer_is_opposite_to_a_need(): void
    {
        $this->assertSame(ExchangeType::Need, ExchangeType::Offer->opposite());
    }

    #[Test]
    public function a_need_is_opposite_to_an_offer(): void
    {
        $this->assertSame(ExchangeType::Offer, ExchangeType::Need->opposite());
    }
}
