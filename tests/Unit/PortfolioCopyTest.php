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
        $text = $this->copy->forTotalValue(50);

        $this->assertStringContainsString('shrimp', $text);
    }

    public function test_whale_tier_for_large_portfolio(): void
    {
        $text = $this->copy->forTotalValue(250000);

        $this->assertStringContainsString('whale', strtolower($text));
    }

    public function test_boundary_at_exactly_one_hundred_is_shrimp(): void
    {
        $text = $this->copy->forTotalValue(100);

        $this->assertStringContainsString('shrimp', $text);
    }
}
