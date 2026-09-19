<?php

namespace Tests\Unit;

use App\Services\LiquidityBalancer;
use PHPUnit\Framework\TestCase;

class LiquidityBalancerTest extends TestCase
{
    private LiquidityBalancer $balancer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->balancer = new LiquidityBalancer();
    }

    public function test_balanced_holdings_require_no_trade(): void
    {
        $result = $this->balancer->calculate([
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 1,
            'coinB_adjusted' => 2,
        ]);

        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-10);
        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-10);
        $this->assertEqualsWithDelta(1.0, $result['finalCoinA'], 1e-10);
        $this->assertEqualsWithDelta(2.0, $result['finalCoinB'], 1e-10);
        $this->assertEqualsWithDelta(200.0, $result['totalValueAdjusted'], 1e-10);
    }

    public function test_sell_coin_a_and_buy_coin_b_when_a_is_overweight(): void
    {
        // Reference 50/50 by value: 1 A @ 100 + 2 B @ 50 = 200
        // Holdings: 2 A @ 100 + 0 B = 200 → sell 1 A, buy 2 B
        $result = $this->balancer->calculate([
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 2,
            'coinB_adjusted' => 0,
        ]);

        $this->assertSame('sell', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(1.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-10);
        $this->assertEqualsWithDelta(2.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-10);
        $this->assertEqualsWithDelta(1.0, $result['finalCoinA'], 1e-10);
        $this->assertEqualsWithDelta(2.0, $result['finalCoinB'], 1e-10);
    }

    public function test_buy_coin_a_and_sell_coin_b_when_b_is_overweight(): void
    {
        $result = $this->balancer->calculate([
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 0,
            'coinB_adjusted' => 4,
        ]);

        $this->assertSame('buy', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertSame('sell', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(1.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-10);
        $this->assertEqualsWithDelta(2.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-10);
    }

    public function test_zero_adjusted_holdings_yield_zero_totals(): void
    {
        $result = $this->balancer->calculate([
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 10,
            'coinB_price' => 10,
            'coinA_adjusted' => 0,
            'coinB_adjusted' => 0,
        ]);

        $this->assertEqualsWithDelta(0.0, $result['totalValueAdjusted'], 1e-10);
        $this->assertEqualsWithDelta(0.0, $result['finalCoinA'], 1e-10);
        $this->assertEqualsWithDelta(0.0, $result['finalCoinB'], 1e-10);
    }
}
