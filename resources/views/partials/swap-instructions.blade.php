@php
    $sellA = ($unitsOfCoinsResult['A']['text'] ?? '') === 'sell';
    $fromCoin = $sellA ? 'A' : 'B';
    $toCoin = $sellA ? 'B' : 'A';
    $fromAmount = round($unitsOfCoinsResult[$fromCoin]['amount'], 6);
    $toAmount = round($unitsOfCoinsResult[$toCoin]['amount'], 6);
@endphp
<p class="instructions">
    <strong>No more fooling around. Simply go to
        <a href="https://swap.defillama.com" class="link">Llamaswap</a>,
        swap <em>{{ $fromAmount }}</em> of coin {{ $fromCoin }} for
        <em>{{ $toAmount }}</em> of coin {{ $toCoin }} on the correct blockchain.
        After the swap, then add the liquidity to your preferred DEX
    </strong>
</p>
