@php
    $actionA = $unitsOfCoinsResult['A']['text'] ?? '';
    $actionB = $unitsOfCoinsResult['B']['text'] ?? '';
    $displayA = format_trimmed_number($unitsOfCoinsResult['A']['amount'] ?? 0);
    $displayB = format_trimmed_number($unitsOfCoinsResult['B']['amount'] ?? 0);
    $displayFinalA = format_trimmed_number($finalCoinA);
    $displayFinalB = format_trimmed_number($finalCoinB);
    $actionClassA = in_array($actionA, ['buy', 'sell'], true) ? $actionA : 'hold';
    $actionClassB = in_array($actionB, ['buy', 'sell'], true) ? $actionB : 'hold';
@endphp

<div class="receipt" aria-label="Buy and sell plan">
    <div class="receipt-row receipt-row--action receipt-row--{{ $actionClassA }}">
        <span class="receipt-label">
            <span class="receipt-action" data-action="{{ $actionClassA }}">{{ $actionA }}</span>
            Coin A
        </span>
        <span class="receipt-value">{{ $displayA }}</span>
    </div>
    <div class="receipt-row receipt-row--action receipt-row--{{ $actionClassB }}">
        <span class="receipt-label">
            <span class="receipt-action" data-action="{{ $actionClassB }}">{{ $actionB }}</span>
            Coin B
        </span>
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
