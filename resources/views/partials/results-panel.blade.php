@php
    $modeLabels = [
        'rebalance' => 'Rebalance holdings',
        'deploy_budget' => 'Deploy budget',
        'keep_a' => 'Keep Coin A',
        'keep_b' => 'Keep Coin B',
    ];
    $pctA = isset($propA) && $propA !== null ? round($propA * 100, 1) : null;
    $pctB = isset($propB) && $propB !== null ? round($propB * 100, 1) : null;
@endphp

<section class="results-container results-container--filled" id="results">
    <div class="results-heading-row">
        <h2>Your next moves</h2>
        @if (!empty($personality))
            <span class="tier-badge tier-badge--compact" aria-label="Portfolio tier: {{ $personality['label'] }}">
                <span class="tier-badge__emoji" aria-hidden="true">{{ $personality['emoji'] }}</span>
                <span class="tier-badge__label">{{ $personality['label'] }}</span>
            </span>
        @endif
    </div>

    <p class="mode-result-label"><strong>Mode:</strong> {{ $modeLabels[$mode] ?? $mode }}</p>

    @if ($pctA !== null && $pctB !== null)
        <div class="pool-weights" aria-label="Pool value weights">
            <div class="pool-weights__label">
                Pool value weights:
                <strong>{{ $pctA }}% Coin A</strong> /
                <strong>{{ $pctB }}% Coin B</strong>
            </div>
            <div class="pool-weights__bar" role="img" aria-label="{{ $pctA }} percent Coin A, {{ $pctB }} percent Coin B">
                <span class="pool-weights__segment pool-weights__segment--a" style="width: {{ $pctA }}%"></span>
                <span class="pool-weights__segment pool-weights__segment--b" style="width: {{ $pctB }}%"></span>
            </div>
        </div>
    @endif

    @include('partials.results-receipt', [
        'unitsOfCoinsResult' => $unitsOfCoinsResult,
        'finalCoinA' => $finalCoinA,
        'finalCoinB' => $finalCoinB,
    ])

    @include('partials.swap-instructions', ['unitsOfCoinsResult' => $unitsOfCoinsResult])

    @if (!is_null($capitalDeployed ?? null))
        <p class="capital-line">Capital deployed: <em>${{ number_format((float) $capitalDeployed, 2) }}</em></p>
    @endif
    @if (!is_null($capitalRequired ?? null))
        <p class="capital-line">Capital required to buy the missing side: <em>${{ number_format((float) $capitalRequired, 2) }}</em></p>
    @endif
    @if (($slippageApplied ?? 0) > 0)
        <p class="slippage-note">Buy amounts include a {{ rtrim(rtrim(number_format((float) $slippageApplied, 4), '0'), '.') }}% slippage buffer.</p>
    @endif

    @if (!empty($warnings))
        <ul class="warnings-list">
            @foreach ($warnings as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
    @endif

    @include('partials.personality', ['personality' => $personality ?? null])

    <details class="breakdown-details">
        <summary>How we got this</summary>
        <div class="breakdown-body">
            <p>We convert the pool reference amounts into value weights, compare them to your holdings or budget, then compute the buys and sells needed to match.</p>
            @if (($slippageApplied ?? 0) > 0 && !empty($idealBuys))
                <p>
                    Ideal buy Coin A (before buffer): <em>{{ rtrim(rtrim(number_format((float) $idealBuys['A'], 8, '.', ''), '0'), '.') ?: '0' }}</em><br>
                    Ideal buy Coin B (before buffer): <em>{{ rtrim(rtrim(number_format((float) $idealBuys['B'], 8, '.', ''), '0'), '.') ?: '0' }}</em>
                </p>
            @endif
            <p>
                Amount of Coin A to {{ $unitsOfCoinsResult['A']['text'] }}:
                <em>{{ $unitsOfCoinsResult['A']['amount'] }}</em><br>
                Amount of Coin B to {{ $unitsOfCoinsResult['B']['text'] }}:
                <em>{{ $unitsOfCoinsResult['B']['amount'] }}</em><br>
                Final Coin A amount: <em>{{ $finalCoinA }}</em><br>
                Final Coin B amount: <em>{{ $finalCoinB }}</em>
            </p>
        </div>
    </details>
</section>
