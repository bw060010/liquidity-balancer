<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateRequest;
use App\Services\LiquidityBalancer;
use App\Services\PortfolioCopy;
use Illuminate\Contracts\View\View;

class CalculationController extends Controller
{
    public function __construct(
        private readonly LiquidityBalancer $liquidityBalancer,
        private readonly PortfolioCopy $portfolioCopy,
    ) {
    }

    public function showForm(): View
    {
        return view('calculate', [
            'input' => [],
        ]);
    }

    public function performCalculation(CalculateRequest $request): View
    {
        $data = $request->validated();
        $data['coinA_adjusted'] = $data['coinA_adjusted'] ?? 0;
        $data['coinB_adjusted'] = $data['coinB_adjusted'] ?? 0;
        $data['slippage_pct'] = $data['slippage_pct'] ?? 0;

        $calcResults = $this->liquidityBalancer->calculate($data);

        return view('calculate', [
            'submitted' => true,
            'input' => $data,
            'personality' => $this->portfolioCopy->forTotalValue($calcResults['totalValueAdjusted']),
            'unitsOfCoinsResult' => $calcResults['unitsOfCoinsResult'],
            'finalCoinA' => $calcResults['finalCoinA'],
            'finalCoinB' => $calcResults['finalCoinB'],
            'mode' => $calcResults['mode'],
            'warnings' => $calcResults['warnings'],
            'capitalRequired' => $calcResults['capitalRequired'],
            'capitalDeployed' => $calcResults['capitalDeployed'],
            'slippageApplied' => $calcResults['slippageApplied'],
            'idealBuys' => $calcResults['idealBuys'],
            'totalValue' => $calcResults['totalValueAdjusted'],
            'propA' => $calcResults['propA'],
            'propB' => $calcResults['propB'],
        ]);
    }
}
