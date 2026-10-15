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
            'modes' => LiquidityBalancer::MODE_CATALOG,
        ]);
    }

    public function performCalculation(CalculateRequest $request): View
    {
        $data = $request->validated();
        $result = $this->liquidityBalancer->calculate($data);

        return view('calculate', [
            'submitted' => true,
            'input' => $data,
            'modes' => LiquidityBalancer::MODE_CATALOG,
            'personality' => $this->portfolioCopy->forTotalValue($result['totalValueAdjusted']),
            'result' => $result,
        ]);
    }
}
