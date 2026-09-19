@php
    $actionA = $unitsOfCoinsResult['A']['text'] ?? '';
    $actionB = $unitsOfCoinsResult['B']['text'] ?? '';
    $amountA = round($unitsOfCoinsResult['A']['amount'] ?? 0, 6);
    $amountB = round($unitsOfCoinsResult['B']['amount'] ?? 0, 6);

    $needsBuyA = $actionA === 'buy' && $amountA > 0;
    $needsBuyB = $actionB === 'buy' && $amountB > 0;
    $needsSellA = $actionA === 'sell' && $amountA > 0;
    $needsSellB = $actionB === 'sell' && $amountB > 0;
@endphp

@if (!$needsBuyA && !$needsBuyB && !$needsSellA && !$needsSellB)
    <p class="instructions instructions--balanced">
        <strong>Already balanced.</strong>
        Your planned amounts already match the pool ratio — nice work. Add liquidity on your preferred DEX.
    </p>
@elseif ($needsBuyA && $needsBuyB && !$needsSellA && !$needsSellB)
    <p class="instructions">
        <strong>Time to even the scales.</strong>
        Acquire <em>{{ $amountA }}</em> of Coin A and
        <em>{{ $amountB }}</em> of Coin B (for example via
        <a href="https://swap.defillama.com" class="llamaswap-link">Llamaswap</a>
        on the correct blockchain — two buys or one routing path). After you have both sides, add the liquidity to your preferred DEX.
    </p>
@elseif ($needsSellA && $needsBuyB)
    <p class="instructions">
        <strong>Time to even the scales.</strong>
        Go to
        <a href="https://swap.defillama.com" class="llamaswap-link">Llamaswap</a>,
        swap <em>{{ $amountA }}</em> of Coin A for
        <em>{{ $amountB }}</em> of Coin B on the correct blockchain.
        After the swap, add the liquidity to your preferred DEX.
    </p>
@elseif ($needsSellB && $needsBuyA)
    <p class="instructions">
        <strong>Time to even the scales.</strong>
        Go to
        <a href="https://swap.defillama.com" class="llamaswap-link">Llamaswap</a>,
        swap <em>{{ $amountB }}</em> of Coin B for
        <em>{{ $amountA }}</em> of Coin A on the correct blockchain.
        After the swap, add the liquidity to your preferred DEX.
    </p>
@elseif ($needsBuyA && !$needsBuyB && !$needsSellA && !$needsSellB)
    <p class="instructions">
        <strong>Time to even the scales.</strong>
        Go to
        <a href="https://swap.defillama.com" class="llamaswap-link">Llamaswap</a>,
        buy <em>{{ $amountA }}</em> of Coin A on the correct blockchain.
        After the purchase, add the liquidity to your preferred DEX.
    </p>
@elseif ($needsBuyB && !$needsBuyA && !$needsSellA && !$needsSellB)
    <p class="instructions">
        <strong>Time to even the scales.</strong>
        Go to
        <a href="https://swap.defillama.com" class="llamaswap-link">Llamaswap</a>,
        buy <em>{{ $amountB }}</em> of Coin B on the correct blockchain.
        After the purchase, add the liquidity to your preferred DEX.
    </p>
@else
    <p class="instructions">
        <strong>Review the plan below,</strong>
        execute the listed buys/sells (for example via
        <a href="https://swap.defillama.com" class="llamaswap-link">Llamaswap</a>),
        then add liquidity on your preferred DEX.
    </p>
@endif
