<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculateTest extends TestCase
{
    public function test_home_redirects_to_calculate(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/calculate');
    }

    public function test_calculate_form_is_displayed(): void
    {
        $response = $this->get(route('calculate.show'));

        $response->assertOk();
        $response->assertSee('Liquidity Balancer', false);
        $response->assertSee('Plan your deposit', false);
        $response->assertSee('Your buy/sell plan will show up here', false);
        $response->assertSee('How this works', false);
        $response->assertSee('name="coinB_initial"', false);
        $response->assertSee('Even the scales', false);
        $response->assertSee('lang="en"', false);
    }

    public function test_valid_calculation_shows_results(): void
    {
        $response = $this->post(route('calculate.store'), [
            'mode' => 'rebalance',
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 2,
            'coinB_adjusted' => 0,
        ]);

        $response->assertOk();
        $response->assertSee('Your next moves', false);
        $response->assertSee('How we got this', false);
        $response->assertSee('Pool value weights', false);
        $response->assertSee('Mode:', false);
        $response->assertSee('Rebalance holdings', false);
        $response->assertSee('sell', false);
        $response->assertSee('buy', false);
        $response->assertSee('Llamaswap', false);
        $response->assertSee('Time to even the scales', false);
        $response->assertSee('A word from the reef', false);
        $response->assertSee('id="results"', false);
        $response->assertDontSee('{!!', false);
    }

    public function test_deploy_budget_mode_shows_dual_buy_instructions(): void
    {
        $response = $this->post(route('calculate.store'), [
            'mode' => 'deploy_budget',
            'new_capital' => 1000,
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 0,
            'coinB_adjusted' => 0,
            'slippage_pct' => 0,
        ]);

        $response->assertOk();
        $response->assertSee('Deploy budget', false);
        $response->assertSee('Capital deployed', false);
        $response->assertSee('Acquire', false);
        $response->assertSee('5', false);
        $response->assertSee('10', false);
    }

    public function test_balanced_holdings_show_already_balanced_message(): void
    {
        $response = $this->post(route('calculate.store'), [
            'mode' => 'rebalance',
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 1,
            'coinB_adjusted' => 2,
        ]);

        $response->assertOk();
        $response->assertSee('Already balanced', false);
    }

    public function test_form_posts_to_results_anchor(): void
    {
        $response = $this->get(route('calculate.show'));

        $response->assertOk();
        $response->assertSee('#results', false);
        $response->assertDontSee('#ad-placeholder', false);
    }

    public function test_invalid_zero_price_shows_validation_errors(): void
    {
        $response = $this->from(route('calculate.show'))->post(route('calculate.store'), [
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 0,
            'coinB_price' => 50,
            'coinA_adjusted' => 1,
            'coinB_adjusted' => 1,
        ]);

        $response->assertRedirect(route('calculate.show'));
        $response->assertSessionHasErrors(['coinA_price']);
    }

    public function test_negative_holdings_are_rejected(): void
    {
        $response = $this->from(route('calculate.show'))->post(route('calculate.store'), [
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => -1,
            'coinB_adjusted' => 1,
        ]);

        $response->assertRedirect(route('calculate.show'));
        $response->assertSessionHasErrors(['coinA_adjusted']);
    }

    public function test_old_input_is_repopulated_after_validation_failure(): void
    {
        $response = $this->from(route('calculate.show'))->post(route('calculate.store'), [
            'coinA_initial' => 1,
            'coinB_initial' => 3.5,
            'coinA_price' => 0,
            'coinB_price' => 50,
            'coinA_adjusted' => 1,
            'coinB_adjusted' => 1,
        ]);

        $response->assertRedirect(route('calculate.show'));

        $followUp = $this->followRedirects($response);
        $followUp->assertSee('value="3.5"', false);
    }
}
