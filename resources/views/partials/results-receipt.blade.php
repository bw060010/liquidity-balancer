@php
    $actionA = $unitsOfCoinsResult['A']['text'] ?? '';
    $actionB = $unitsOfCoinsResult['B']['text'] ?? '';
    $displayA = format_trimmed_number($unitsOfCoinsResult['A']['amount'] ?? 0);
    $displayB = format_trimmed_number($unitsOfCoinsResult['B']['amount'] ?? 0);
    $displayFinalA = format_trimmed_number($finalCoinA);
    $displayFinalB = format_trimmed_number($finalCoinB);
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
