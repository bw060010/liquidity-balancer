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
    <p class="instructions">
        <strong>No swap needed. Your planned balances already match the pool ratio — add liquidity on your preferred DEX.</strong>
    </p>
@elseif ($needsBuyA && $needsBuyB && !$needsSellA && !$needsSellB)
    <p class="instructions">
        <strong>No more fooling around. Acquire <em>{{ $amountA }}</em> of coin A and
            <em>{{ $amountB }}</em> of coin B (for example via
            <a href="https://swap.defillama.com" class="link">Llamaswap</a>
            on the correct blockchain — two buys or one routing path). After you have both sides, add the liquidity to your preferred DEX.
        </strong>
    </p>
@elseif ($needsSellA && $needsBuyB)
    <p class="instructions">
        <strong>No more fooling around. Simply go to
            <a href="https://swap.defillama.com" class="link">Llamaswap</a>,
            swap <em>{{ $amountA }}</em> of coin A for
            <em>{{ $amountB }}</em> of coin B on the correct blockchain.
            After the swap, then add the liquidity to your preferred DEX
        </strong>
    </p>
@elseif ($needsSellB && $needsBuyA)
    <p class="instructions">
        <strong>No more fooling around. Simply go to
            <a href="https://swap.defillama.com" class="link">Llamaswap</a>,
            swap <em>{{ $amountB }}</em> of coin B for
            <em>{{ $amountA }}</em> of coin A on the correct blockchain.
            After the swap, then add the liquidity to your preferred DEX
        </strong>
    </p>
@elseif ($needsBuyA && !$needsBuyB && !$needsSellA && !$needsSellB)
    <p class="instructions">
        <strong>No more fooling around. Simply go to
            <a href="https://swap.defillama.com" class="link">Llamaswap</a>,
            buy <em>{{ $amountA }}</em> of coin A on the correct blockchain.
            After the purchase, then add the liquidity to your preferred DEX
        </strong>
    </p>
@elseif ($needsBuyB && !$needsBuyA && !$needsSellA && !$needsSellB)
    <p class="instructions">
        <strong>No more fooling around. Simply go to
            <a href="https://swap.defillama.com" class="link">Llamaswap</a>,
            buy <em>{{ $amountB }}</em> of coin B on the correct blockchain.
            After the purchase, then add the liquidity to your preferred DEX
        </strong>
    </p>
@else
    <p class="instructions">
        <strong>Review the breakdown below, execute the listed buys/sells (for example via
            <a href="https://swap.defillama.com" class="link">Llamaswap</a>),
            then add liquidity on your preferred DEX.
        </strong>
    </p>
@endif
