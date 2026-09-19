<?php

namespace Tests\Unit;

use App\Services\LiquidityRebalancer;
use PHPUnit\Framework\TestCase;

class LiquidityRebalancerTest extends TestCase
{
    private LiquidityRebalancer $rebalancer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebalancer = new LiquidityRebalancer();
    }

    public function test_rebalance_matches_legacy_formula(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_REBALANCE,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 5,
        ]);

        // propA = 100/(100+100) = 0.5
        // total = 10*100 + 5*50 = 1250
        // target each side = 625
        // sell A = (1000-625)/100 = 3.75
        // buy B = (625-250)/50 = 7.5
        $this->assertSame(LiquidityRebalancer::MODE_REBALANCE, $result['mode']);
        $this->assertSame('sell', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertEqualsWithDelta(3.75, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(7.5, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(6.25, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(12.5, $result['finalCoinB'], 1e-9);
        $this->assertEqualsWithDelta(1250.0, $result['totalValue'], 1e-9);
        $this->assertEmpty($result['warnings']);
    }

    public function test_already_balanced_holdings_need_no_trade(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_REBALANCE,
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 10,
            'coinB_price' => 10,
            'coinA_adjusted' => 5,
            'coinB_adjusted' => 5,
        ]);

        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(5.0, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(5.0, $result['finalCoinB'], 1e-9);
    }

    public function test_rebalance_zero_holdings_warns(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_REBALANCE,
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 10,
            'coinB_price' => 10,
            'coinA_adjusted' => 0,
            'coinB_adjusted' => 0,
        ]);

        $this->assertNotEmpty($result['warnings']);
        $this->assertEqualsWithDelta(0.0, $result['totalValue'], 1e-9);
    }

    public function test_deploy_budget_splits_equal_value_for_fifty_fifty_pool(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_DEPLOY_BUDGET,
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 20,
            'coinB_price' => 20,
            'coinA_adjusted' => 999,
            'coinB_adjusted' => 999,
            'new_capital' => 1000,
        ]);

        $this->assertSame(LiquidityRebalancer::MODE_DEPLOY_BUDGET, $result['mode']);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(25.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertEqualsWithDelta(25.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(25.0, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(25.0, $result['finalCoinB'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $result['capitalDeployed'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $result['totalValue'], 1e-9);
    }

    public function test_deploy_budget_requires_positive_capital(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_DEPLOY_BUDGET,
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
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_KEEP_A,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 0,
        ]);

        // propA = 0.5; finalB = (10*100*0.5/0.5)/50 = 20
        $this->assertSame(LiquidityRebalancer::MODE_KEEP_A, $result['mode']);
        $this->assertEqualsWithDelta(10.0, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(20.0, $result['finalCoinB'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(20.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $result['capitalRequired'], 1e-9);
        $this->assertEmpty($result['warnings']);
    }

    public function test_keep_a_with_excess_b_warns_and_does_not_sell_into_a(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_KEEP_A,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 30,
        ]);

        $this->assertEqualsWithDelta(10.0, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(20.0, $result['finalCoinB'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertNull($result['capitalRequired']);
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('excess Coin B', $result['warnings'][0]);
    }

    public function test_keep_b_buys_missing_a(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_KEEP_B,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 0,
            'coinB_adjusted' => 20,
        ]);

        // propA = 0.5; finalA = (20*50*0.5/0.5)/100 = 10
        $this->assertSame(LiquidityRebalancer::MODE_KEEP_B, $result['mode']);
        $this->assertEqualsWithDelta(10.0, $result['finalCoinA'], 1e-9);
        $this->assertEqualsWithDelta(20.0, $result['finalCoinB'], 1e-9);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertEqualsWithDelta(10.0, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $result['capitalRequired'], 1e-9);
    }

    public function test_keep_a_with_zero_holdings_warns(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_KEEP_A,
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 10,
            'coinB_price' => 10,
            'coinA_adjusted' => 0,
            'coinB_adjusted' => 5,
        ]);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Coin A holdings', $result['warnings'][0]);
    }

    public function test_slippage_inflates_buys_only(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_REBALANCE,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 10,
            'coinB_adjusted' => 5,
            'slippage_pct' => 1,
        ]);

        $this->assertEqualsWithDelta(1.0, $result['slippageApplied'], 1e-9);
        $this->assertSame('sell', $result['unitsOfCoinsResult']['A']['text']);
        $this->assertEqualsWithDelta(3.75, $result['unitsOfCoinsResult']['A']['amount'], 1e-9);
        $this->assertSame('buy', $result['unitsOfCoinsResult']['B']['text']);
        $this->assertEqualsWithDelta(7.5 * 1.01, $result['unitsOfCoinsResult']['B']['amount'], 1e-9);
        $this->assertEqualsWithDelta(7.5, $result['idealBuys']['B'], 1e-9);
    }

    public function test_zero_price_returns_warning(): void
    {
        $result = $this->rebalancer->calculate([
            'mode' => LiquidityRebalancer::MODE_REBALANCE,
            'coinA_initial' => 1,
            'coinB_initial' => 1,
            'coinA_price' => 0,
            'coinB_price' => 10,
            'coinA_adjusted' => 1,
            'coinB_adjusted' => 1,
        ]);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('greater than zero', $result['warnings'][0]);
    }
}
