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
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_rebalance_matches_legacy_formula(): void
    {
        $result = $this->balancer->calculate([
            'mode' => LiquidityBalancer::MODE_REBALANCE,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 5,
        ]);

        $this->assertSame(LiquidityBalancer::MODE_REBALANCE, $result['mode']);
        $this->assertSame('sell', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertEqualsWithDelta(3.75, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(7.5, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(6.25, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(12.5, $result['finalCoinB'], 1e-9);
    }

    public function test_deploy_budget_splits_equal_value_for_fifty_fifty_pool(): void
    {
        $result = $this->balancer->calculate([
            'mode' => LiquidityBalancer::MODE_DEPLOY_BUDGET,
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 20,
            'coinB_price' => 20,
            'coinA_adjusted' => 999,
            'coinB_adjusted' => 999,
            'new_capital' => 1000,
        ]);

        $this->assertSame(LiquidityBalancer::MODE_DEPLOY_BUDGET, $result['mode']);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(25.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertEqualsWithDelta(25.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $result['capitalDeployed'], 1e-9);
    }

    public function test_deploy_budget_requires_positive_capital(): void
    {
        $result = $this->balancer->calculate([
            'mode' => LiquidityBalancer::MODE_DEPLOY_BUDGET,
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 10,
            'coinB_price' => 10,
            'new_capital' => 0,
        ]);

        $this->assertNotEmpty($result['warnings']);
        $this->assertNull($result['capitalDeployed']);
    }

    public function test_keep_a_buys_missing_b_and_reports_capital_required(): void
    {
        $result = $this->balancer->calculate([
            'mode' => LiquidityBalancer::MODE_KEEP_A,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 0,
        ]);

        $this->assertEqualsWithDelta(10.0, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(20.0, $result['finalCoinB'], 1e-9);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(20.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $result['capitalRequired'], 1e-9);
    }

    public function test_keep_a_with_excess_b_warns_and_does_not_sell_into_a(): void
    {
        $result = $this->balancer->calculate([
            'mode' => LiquidityBalancer::MODE_KEEP_A,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 30,
        ]);

        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertNull($result['capitalRequired']);
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('excess Coin B', $result['warnings'][0]);
    }

    public function test_keep_b_buys_missing_a(): void
    {
        $result = $this->balancer->calculate([
            'mode' => LiquidityBalancer::MODE_KEEP_B,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 0,
            'coinB_adjusted' => 20,
        ]);

        $this->assertSame('buy', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertEqualsWithDelta(10.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $result['capitalRequired'], 1e-9);
    }

    public function test_slippage_inflates_buys_only(): void
    {
        $result = $this->balancer->calculate([
            'mode' => LiquidityBalancer::MODE_REBALANCE,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 5,
            'slippage_pct' => 1,
        ]);

        $this->assertSame('sell', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertEqualsWithDelta(3.75, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(7.5 * 1.01, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
    }

    public function test_result_includes_pool_value_weights(): void
    {
        $result = $this->balancer->calculate([
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 1,
            'coinB_adjusted' => 2,
        ]);

        $this->assertEqualsWithDelta(0.5, $result['propA'], 1e-10);
        $this->assertEqualsWithDelta(0.5, $result['propB'], 1e-10);
    }

    public function test_invalid_inputs_return_null_pool_weights(): void
    {
        $result = $this->balancer->calculate([
            'coinA_initial' => 0,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 1,
            'coinB_adjusted' => 2,
        ]);

        $this->assertNull($result['propA']);
        $this->assertNull($result['propB']);
    }
}
