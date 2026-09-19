<?php

namespace Tests\Unit;

use App\Services\PortfolioCopy;
use Tests\TestCase;

class PortfolioCopyTest extends TestCase
{
    private PortfolioCopy $copy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->copy = new PortfolioCopy();
    }

    public function test_shrimp_tier_for_small_portfolio(): void
    {
        $personality = $this->copy->forTotalValue(50);

        $this->assertSame('shrimp', $personality['id']);
        $this->assertSame('Shrimp', $personality['label']);
        $this->assertStringContainsString('shrimp', strtolower($personality['short']));
        $this->assertNotEmpty($personality['full']);
    }

    public function test_whale_tier_for_large_portfolio(): void
    {
        $personality = $this->copy->forTotalValue(250000);

        $this->assertSame('whale', $personality['id']);
        $this->assertSame('Whale', $personality['label']);
        $this->assertStringContainsString('whale', strtolower($personality['short']));
    }

    public function test_boundary_at_exactly_one_hundred_is_shrimp(): void
    {
        $personality = $this->copy->forTotalValue(100);

        $this->assertSame('shrimp', $personality['id']);
        $this->assertStringContainsString('shrimp', strtolower($personality['full']));
    }
}
