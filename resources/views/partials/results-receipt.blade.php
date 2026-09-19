@php
    $actionA = $unitsOfCoinsResult['A']['text'] ?? '';
    $actionB = $unitsOfCoinsResult['B']['text'] ?? '';
    $amountA = $unitsOfCoinsResult['A']['amount'] ?? 0;
    $amountB = $unitsOfCoinsResult['B']['amount'] ?? 0;

    $formatAmount = function ($amount): string {
        $formatted = rtrim(rtrim(number_format((float) $amount, 8, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    };

    $displayA = $formatAmount($amountA);
    $displayB = $formatAmount($amountB);
    $displayFinalA = $formatAmount($finalCoinA);
    $displayFinalB = $formatAmount($finalCoinB);
@endphp

<div class="receipt" aria-label="Buy and sell plan">
    <div class="receipt-row">
        <span class="receipt-label">Coin A to {{ $actionA }}</span>
        <span class="receipt-value">{{ $displayA }}</span>
    </div>
    <div class="receipt-row">
        <span class="receipt-label">Coin B to {{ $actionB }}</span>
        <span class="receipt-value">{{ $displayB }}</span>
    </div>
    <div class="receipt-row receipt-row--final">
        <span class="receipt-label">Final Coin A</span>
        <span class="receipt-value">{{ $displayFinalA }}</span>
    </div>
    <div class="receipt-row receipt-row--final">
        <span class="receipt-label">Final Coin B</span>
        <span class="receipt-value">{{ $displayFinalB }}</span>
    </div>
</div>
