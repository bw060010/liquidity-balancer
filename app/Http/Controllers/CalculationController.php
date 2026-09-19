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
        $calcResults = $this->liquidityBalancer->calculate($data);

        return view('calculate', [
            'submitted' => true,
            'input' => $data,
            'text' => $this->portfolioCopy->forTotalValue($calcResults['totalValueAdjusted']),
            'unitsOfCoinsResult' => $calcResults['unitsOfCoinsResult'],
            'finalCoinA' => $calcResults['finalCoinA'],
            'finalCoinB' => $calcResults['finalCoinB'],
        ]);
    }
}
