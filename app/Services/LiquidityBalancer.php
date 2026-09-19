<?php

namespace App\Services;

class LiquidityBalancer
{
    /**
     * Calculate LP rebalance amounts from reference proportions and current holdings.
     *
     * @param  array{
     *     coinA_initial: float|int|string,
     *     coinB_initial: float|int|string,
     *     coinA_price: float|int|string,
     *     coinB_price: float|int|string,
     *     coinA_adjusted: float|int|string,
     *     coinB_adjusted: float|int|string
     * }  $data
     * @return array{
     *     totalValueAdjusted: float,
     *     unitsOfCoinsResult: array<string, array{text: string, amount: float}>,
     *     finalCoinA: float,
     *     finalCoinB: float
     * }
     */
    public function calculate(array $data): array
    {
        $coinAInitial = (float) $data['coinA_initial'];
        $coinBInitial = (float) $data['coinB_initial'];
        $coinAPrice = (float) $data['coinA_price'];
        $coinBPrice = (float) $data['coinB_price'];
        $coinAAdjusted = (float) $data['coinA_adjusted'];
        $coinBAdjusted = (float) $data['coinB_adjusted'];

        $initialValueA = $coinAInitial * $coinAPrice;
        $initialValueB = $coinBInitial * $coinBPrice;
        $initialProportionA = $initialValueA / ($initialValueA + $initialValueB);

        $adjustedValueA = $coinAAdjusted * $coinAPrice;
        $adjustedValueB = $coinBAdjusted * $coinBPrice;
        $totalValueAdjusted = $adjustedValueA + $adjustedValueB;

        $targetValueA = $totalValueAdjusted * $initialProportionA;
        $targetValueB = $totalValueAdjusted * (1 - $initialProportionA);

        $unitsOfCoinAToSell = ($adjustedValueA - $targetValueA) / $coinAPrice;
        $unitsOfCoinBToSell = ($adjustedValueB - $targetValueB) / $coinBPrice;

        $finalCoinA = $coinAAdjusted - $unitsOfCoinAToSell;
        $finalCoinB = $coinBAdjusted - $unitsOfCoinBToSell;

        return [
            'totalValueAdjusted' => $totalValueAdjusted,
            'unitsOfCoinsResult' => [
                'A' => $this->tradeDirection($unitsOfCoinAToSell),
                'B' => $this->tradeDirection($unitsOfCoinBToSell),
            ],
            'finalCoinA' => $finalCoinA,
            'finalCoinB' => $finalCoinB,
        ];
    }

    /**
     * @return array{text: string, amount: float}
     */
    private function tradeDirection(float $unitsToSell): array
    {
        return [
            'text' => $unitsToSell < 0 ? 'buy' : 'sell',
            'amount' => abs($unitsToSell),
        ];
    }
}
