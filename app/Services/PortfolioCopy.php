<?php

namespace App\Services;

class PortfolioCopy
{
    /**
     * Return the portfolio-size personality message for a given total adjusted value.
     */
    public function forTotalValue(float $totalValue): string
    {
        $tiers = config('portfolio_tiers.messages', []);

        foreach ($tiers as $upperLimit => $message) {
            if ($totalValue <= (float) $upperLimit) {
                return $message;
            }
        }

        return config('portfolio_tiers.default');
    }
}
