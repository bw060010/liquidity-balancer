<?php

namespace App\Services;

class LiquidityRebalancer
{
    public const MODE_REBALANCE = 'rebalance';
    public const MODE_DEPLOY_BUDGET = 'deploy_budget';
    public const MODE_KEEP_A = 'keep_a';
    public const MODE_KEEP_B = 'keep_b';

    public const MODES = [
        self::MODE_REBALANCE,
        self::MODE_DEPLOY_BUDGET,
        self::MODE_KEEP_A,
        self::MODE_KEEP_B,
    ];

    /**
     * @param  array{
     *     mode?: string,
     *     coinA_initial: float|int|string,
     *     coinB_initial: float|int|string,
     *     coinA_price: float|int|string,
     *     coinB_price: float|int|string,
     *     coinA_adjusted?: float|int|string,
     *     coinB_adjusted?: float|int|string,
     *     new_capital?: float|int|string|null,
     *     slippage_pct?: float|int|string|null
     * }  $data
     * @return array{
     *     mode: string,
     *     unitsOfCoinsResult: array<string, array{text: string, amount: float}>,
     *     finalCoinA: float,
     *     finalCoinB: float,
     *     totalValue: float,
     *     capitalRequired: float|null,
     *     capitalDeployed: float|null,
     *     warnings: list<string>,
     *     slippageApplied: float,
     *     idealBuys: array{A: float, B: float}
     * }
     */
    public function calculate(array $data): array
    {
        $mode = $data['mode'] ?? self::MODE_REBALANCE;
        $slippagePct = max(0.0, (float) ($data['slippage_pct'] ?? 0));

        $coinAInitial = (float) $data['coinA_initial'];
        $coinBInitial = (float) $data['coinB_initial'];
        $coinAPrice = (float) $data['coinA_price'];
        $coinBPrice = (float) $data['coinB_price'];
        $coinAAdjusted = (float) ($data['coinA_adjusted'] ?? 0);
        $coinBAdjusted = (float) ($data['coinB_adjusted'] ?? 0);
        $newCapital = isset($data['new_capital']) && $data['new_capital'] !== null && $data['new_capital'] !== ''
            ? (float) $data['new_capital']
            : null;

        $warnings = [];

        if ($coinAPrice <= 0 || $coinBPrice <= 0 || $coinAInitial <= 0 || $coinBInitial <= 0) {
            return $this->emptyResult($mode, $slippagePct, [
                'Reference amounts and prices must be greater than zero.',
            ]);
        }

        $propA = ($coinAInitial * $coinAPrice) / (($coinAInitial * $coinAPrice) + ($coinBInitial * $coinBPrice));

        return match ($mode) {
            self::MODE_DEPLOY_BUDGET => $this->deployBudget($propA, $coinAPrice, $coinBPrice, $newCapital, $slippagePct, $warnings),
            self::MODE_KEEP_A => $this->keepSide(
                keptSide: 'A',
                propA: $propA,
                coinAPrice: $coinAPrice,
                coinBPrice: $coinBPrice,
                coinAAdjusted: $coinAAdjusted,
                coinBAdjusted: $coinBAdjusted,
                slippagePct: $slippagePct,
                warnings: $warnings
            ),
            self::MODE_KEEP_B => $this->keepSide(
                keptSide: 'B',
                propA: $propA,
                coinAPrice: $coinAPrice,
                coinBPrice: $coinBPrice,
                coinAAdjusted: $coinAAdjusted,
                coinBAdjusted: $coinBAdjusted,
                slippagePct: $slippagePct,
                warnings: $warnings
            ),
            default => $this->rebalance(
                $propA,
                $coinAPrice,
                $coinBPrice,
                $coinAAdjusted,
                $coinBAdjusted,
                $slippagePct,
                $warnings
            ),
        };
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function rebalance(
        float $propA,
        float $coinAPrice,
        float $coinBPrice,
        float $coinAAdjusted,
        float $coinBAdjusted,
        float $slippagePct,
        array $warnings
    ): array {
        $adjustedValueA = $coinAAdjusted * $coinAPrice;
        $adjustedValueB = $coinBAdjusted * $coinBPrice;
        $totalValueAdjusted = $adjustedValueA + $adjustedValueB;

        if ($totalValueAdjusted <= 0) {
            return $this->emptyResult(self::MODE_REBALANCE, $slippagePct, array_merge($warnings, [
                'Holdings total value is zero. Add holdings or use Deploy budget mode.',
            ]));
        }

        $targetValueA = $totalValueAdjusted * $propA;
        $targetValueB = $totalValueAdjusted * (1 - $propA);
        $unitsOfCoinAToSell = ($adjustedValueA - $targetValueA) / $coinAPrice;
        $unitsOfCoinBToSell = ($adjustedValueB - $targetValueB) / $coinBPrice;

        $finalCoinA = $coinAAdjusted - $unitsOfCoinAToSell;
        $finalCoinB = $coinBAdjusted - $unitsOfCoinBToSell;

        $idealBuyA = $unitsOfCoinAToSell < 0 ? abs($unitsOfCoinAToSell) : 0.0;
        $idealBuyB = $unitsOfCoinBToSell < 0 ? abs($unitsOfCoinBToSell) : 0.0;

        $sellA = $unitsOfCoinAToSell > 0 ? $unitsOfCoinAToSell : 0.0;
        $sellB = $unitsOfCoinBToSell > 0 ? $unitsOfCoinBToSell : 0.0;
        $buyA = $this->applySlippage($idealBuyA, $slippagePct);
        $buyB = $this->applySlippage($idealBuyB, $slippagePct);

        return $this->buildResult(
            mode: self::MODE_REBALANCE,
            buyA: $buyA,
            buyB: $buyB,
            sellA: $sellA,
            sellB: $sellB,
            finalCoinA: $finalCoinA,
            finalCoinB: $finalCoinB,
            totalValue: $totalValueAdjusted,
            capitalRequired: null,
            capitalDeployed: null,
            warnings: $warnings,
            slippagePct: $slippagePct,
            idealBuyA: $idealBuyA,
            idealBuyB: $idealBuyB
        );
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function deployBudget(
        float $propA,
        float $coinAPrice,
        float $coinBPrice,
        ?float $newCapital,
        float $slippagePct,
        array $warnings
    ): array {
        if ($newCapital === null || $newCapital <= 0) {
            return $this->emptyResult(self::MODE_DEPLOY_BUDGET, $slippagePct, array_merge($warnings, [
                'Deploy budget mode requires new capital greater than zero.',
            ]));
        }

        $idealBuyA = ($newCapital * $propA) / $coinAPrice;
        $idealBuyB = ($newCapital * (1 - $propA)) / $coinBPrice;
        $buyA = $this->applySlippage($idealBuyA, $slippagePct);
        $buyB = $this->applySlippage($idealBuyB, $slippagePct);

        return $this->buildResult(
            mode: self::MODE_DEPLOY_BUDGET,
            buyA: $buyA,
            buyB: $buyB,
            sellA: 0.0,
            sellB: 0.0,
            finalCoinA: $idealBuyA,
            finalCoinB: $idealBuyB,
            totalValue: $newCapital,
            capitalRequired: null,
            capitalDeployed: $newCapital,
            warnings: $warnings,
            slippagePct: $slippagePct,
            idealBuyA: $idealBuyA,
            idealBuyB: $idealBuyB
        );
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function keepSide(
        string $keptSide,
        float $propA,
        float $coinAPrice,
        float $coinBPrice,
        float $coinAAdjusted,
        float $coinBAdjusted,
        float $slippagePct,
        array $warnings
    ): array {
        $mode = $keptSide === 'A' ? self::MODE_KEEP_A : self::MODE_KEEP_B;

        if ($keptSide === 'A') {
            if ($coinAAdjusted <= 0) {
                return $this->emptyResult($mode, $slippagePct, array_merge($warnings, [
                    'Keep Coin A mode needs Coin A holdings greater than zero. Use Deploy budget or Rebalance instead.',
                ]));
            }

            if ($propA <= 0 || $propA >= 1) {
                return $this->emptyResult($mode, $slippagePct, array_merge($warnings, [
                    'Pool value weights are invalid for keep-side planning.',
                ]));
            }

            $finalCoinA = $coinAAdjusted;
            $finalCoinB = ($coinAAdjusted * $coinAPrice * (1 - $propA) / $propA) / $coinBPrice;
            $idealBuyA = 0.0;
            $idealBuyB = max(0.0, $finalCoinB - $coinBAdjusted);
            $sellA = 0.0;
            $sellB = 0.0;

            if ($coinBAdjusted > $finalCoinB) {
                $unused = $coinBAdjusted - $finalCoinB;
                $warnings[] = sprintf(
                    'You have %.6f excess Coin B unused for this LP deposit (not sold into Coin A).',
                    $unused
                );
            }

            $capitalRequired = $idealBuyB * $coinBPrice;
            $totalValue = ($finalCoinA * $coinAPrice) + ($finalCoinB * $coinBPrice);
        } else {
            if ($coinBAdjusted <= 0) {
                return $this->emptyResult($mode, $slippagePct, array_merge($warnings, [
                    'Keep Coin B mode needs Coin B holdings greater than zero. Use Deploy budget or Rebalance instead.',
                ]));
            }

            if ($propA <= 0 || $propA >= 1) {
                return $this->emptyResult($mode, $slippagePct, array_merge($warnings, [
                    'Pool value weights are invalid for keep-side planning.',
                ]));
            }

            $finalCoinB = $coinBAdjusted;
            $finalCoinA = ($coinBAdjusted * $coinBPrice * $propA / (1 - $propA)) / $coinAPrice;
            $idealBuyB = 0.0;
            $idealBuyA = max(0.0, $finalCoinA - $coinAAdjusted);
            $sellA = 0.0;
            $sellB = 0.0;

            if ($coinAAdjusted > $finalCoinA) {
                $unused = $coinAAdjusted - $finalCoinA;
                $warnings[] = sprintf(
                    'You have %.6f excess Coin A unused for this LP deposit (not sold into Coin B).',
                    $unused
                );
            }

            $capitalRequired = $idealBuyA * $coinAPrice;
            $totalValue = ($finalCoinA * $coinAPrice) + ($finalCoinB * $coinBPrice);
        }

        $buyA = $this->applySlippage($idealBuyA, $slippagePct);
        $buyB = $this->applySlippage($idealBuyB, $slippagePct);

        if ($capitalRequired <= 0) {
            $capitalRequired = null;
        }

        return $this->buildResult(
            mode: $mode,
            buyA: $buyA,
            buyB: $buyB,
            sellA: $sellA,
            sellB: $sellB,
            finalCoinA: $finalCoinA,
            finalCoinB: $finalCoinB,
            totalValue: $totalValue,
            capitalRequired: $capitalRequired,
            capitalDeployed: null,
            warnings: $warnings,
            slippagePct: $slippagePct,
            idealBuyA: $idealBuyA,
            idealBuyB: $idealBuyB
        );
    }

    private function applySlippage(float $idealBuy, float $slippagePct): float
    {
        if ($idealBuy <= 0 || $slippagePct <= 0) {
            return $idealBuy;
        }

        return $idealBuy * (1 + $slippagePct / 100);
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function buildResult(
        string $mode,
        float $buyA,
        float $buyB,
        float $sellA,
        float $sellB,
        float $finalCoinA,
        float $finalCoinB,
        float $totalValue,
        ?float $capitalRequired,
        ?float $capitalDeployed,
        array $warnings,
        float $slippagePct,
        float $idealBuyA,
        float $idealBuyB
    ): array {
        return [
            'mode' => $mode,
            'unitsOfCoinsResult' => [
                'A' => $this->actionFromBuySell($buyA, $sellA),
                'B' => $this->actionFromBuySell($buyB, $sellB),
            ],
            'finalCoinA' => $finalCoinA,
            'finalCoinB' => $finalCoinB,
            'totalValue' => $totalValue,
            'capitalRequired' => $capitalRequired,
            'capitalDeployed' => $capitalDeployed,
            'warnings' => $warnings,
            'slippageApplied' => $slippagePct,
            'idealBuys' => [
                'A' => $idealBuyA,
                'B' => $idealBuyB,
            ],
        ];
    }

    /**
     * @return array{text: string, amount: float}
     */
    private function actionFromBuySell(float $buy, float $sell): array
    {
        if ($buy > 0) {
            return ['text' => 'buy', 'amount' => $buy];
        }

        if ($sell > 0) {
            return ['text' => 'sell', 'amount' => $sell];
        }

        return ['text' => 'buy', 'amount' => 0.0];
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function emptyResult(string $mode, float $slippagePct, array $warnings): array
    {
        return $this->buildResult(
            mode: $mode,
            buyA: 0.0,
            buyB: 0.0,
            sellA: 0.0,
            sellB: 0.0,
            finalCoinA: 0.0,
            finalCoinB: 0.0,
            totalValue: 0.0,
            capitalRequired: null,
            capitalDeployed: null,
            warnings: $warnings,
            slippagePct: $slippagePct,
            idealBuyA: 0.0,
            idealBuyB: 0.0
        );
    }
}
