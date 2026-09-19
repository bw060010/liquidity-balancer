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
        $response->assertSee('Liquidity Balancer Calculator', false);
        $response->assertSee('name="coinB_initial"', false);
    }

    public function test_valid_calculation_shows_results(): void
    {
        $response = $this->post(route('calculate.store'), [
            'coinA_initial' => 1,
            'coinB_initial' => 2,
            'coinA_price' => 100,
            'coinB_price' => 50,
            'coinA_adjusted' => 2,
            'coinB_adjusted' => 0,
        ]);

        $response->assertOk();
        $response->assertSee('Breakdown of the calculation', false);
        $response->assertSee('sell', false);
        $response->assertSee('buy', false);
        $response->assertSee('Llamaswap', false);
        $response->assertDontSee('{!!', false);
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
