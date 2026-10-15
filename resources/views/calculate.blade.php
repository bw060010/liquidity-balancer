<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Liquidity Balancer Calculator | Optimize LP Deposits - Liquidity-Balancer.com</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
</head>

<body>
    @php
        $modes = $modes ?? \App\Services\LiquidityBalancer::MODE_CATALOG;
        $selectedMode = old('mode', $input['mode'] ?? \App\Services\LiquidityBalancer::MODE_REBALANCE);
        $slippageMax = \App\Services\LiquidityBalancer::SLIPPAGE_MAX_PCT;
    @endphp
    <div class="grid-container{{ isset($submitted) ? ' grid-container--has-results' : '' }}">
        <header class="header{{ isset($submitted) ? ' header--settled' : '' }}">
            <div class="logo-container">
                @include('partials.logo')
            </div>
            <div class="text-container">
                <p class="brand-mark">Liquidity-Balancer.com</p>
                <h1>Liquidity Balancer</h1>
                <p class="info">Plan the exact Coin A / Coin B split before you deposit into a DEX liquidity pool.</p>
                <details class="how-it-works">
                    <summary>How this works</summary>
                    <ol>
                        <li>Read the pool’s value weights from the DEX deposit screen.</li>
                        <li>Compare those weights to your holdings or new capital.</li>
                        <li>Buy or sell only what you need so both sides match — then deposit.</li>
                    </ol>
                </details>
            </div>
        </header>

        <section class="form-container">
            <h2>Plan your deposit</h2>
            <form
                action="{{ route('calculate.store') }}#results"
                method="POST"
                class="form"
                id="deposit-planner-form"
                data-mode-rebalance="{{ \App\Services\LiquidityBalancer::MODE_REBALANCE }}"
                data-mode-deploy="{{ \App\Services\LiquidityBalancer::MODE_DEPLOY_BUDGET }}"
                data-mode-keep-a="{{ \App\Services\LiquidityBalancer::MODE_KEEP_A }}"
                data-mode-keep-b="{{ \App\Services\LiquidityBalancer::MODE_KEEP_B }}"
            >
                @csrf
                <div class="section">
                    <fieldset class="mode-fieldset">
                        <legend class="input-label">Deposit mode</legend>
                        <div class="mode-options" role="radiogroup" aria-label="Deposit mode">
                            @foreach ($modes as $modeValue => $modeMeta)
                                <label class="mode-option">
                                    <input type="radio" name="mode" value="{{ $modeValue }}"
                                        {{ $selectedMode === $modeValue ? 'checked' : '' }}>
                                    <span class="mode-option__copy">
                                        <span class="mode-option__title">{{ $modeMeta['label'] }}</span>
                                        <span class="mode-option__subtitle">{{ $modeMeta['subtitle'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('mode')
                            <p class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

                <div class="field-group" id="new-capital-section">
                    <h3 class="field-group__title">New capital</h3>
                    <div class="field-row field-row--single">
                        <div class="section">
                            <x-input-field
                                name="new_capital"
                                label="New capital ($)"
                                :value="old('new_capital', $input['new_capital'] ?? '')"
                                :required="false"
                            >
                                Dollar amount of new capital to split into Coin A and Coin B by the pool value weights. Holdings are ignored in Deploy budget mode.
                            </x-input-field>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <h3 class="field-group__title">Pool ratio</h3>
                    <div class="field-row">
                        <div class="section">
                            <x-input-field
                                name="coinA_initial"
                                label="Coin A Reference Amount"
                                :value="old('coinA_initial', $input['coinA_initial'] ?? '1')"
                                :readonly="true"
                                hint="Fixed at 1 so Coin B is easy to compare"
                            >
                                Fixed at 1 for Coin A to standardize comparison against Coin B
                            </x-input-field>
                        </div>
                        <div class="section">
                            <x-input-field
                                name="coinB_initial"
                                label="Coin B Equivalent Amount"
                                :value="old('coinB_initial', $input['coinB_initial'] ?? '')"
                                hint="From your DEX deposit screen"
                                :tooltip-image="asset('images/tooltips/example_coin_b_equivalent_amount.png')"
                                tooltip-image-alt="Example Coin B Equivalent Amount from a DEX deposit screen"
                            >
                                Enter the amount of Coin B equivalent to 1 unit of Coin A according to the latest DEX deposit rates
                            </x-input-field>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <h3 class="field-group__title">Prices</h3>
                    <div class="field-row">
                        <div class="section">
                            <x-input-field
                                name="coinA_price"
                                label="Price of Coin A"
                                :value="old('coinA_price', $input['coinA_price'] ?? '')"
                            >
                                Enter the latest market price of Coin A (find on CoinGecko or similar)
                            </x-input-field>
                        </div>
                        <div class="section">
                            <x-input-field
                                name="coinB_price"
                                label="Price of Coin B"
                                :value="old('coinB_price', $input['coinB_price'] ?? '')"
                            >
                                Enter the latest market price of Coin B (find on CoinGecko or similar).
                            </x-input-field>
                        </div>
                    </div>
                </div>

                <div class="field-group" id="holdings-section">
                    <h3 class="field-group__title">Your position</h3>
                    <div class="field-row">
                        <div class="section">
                            <x-input-field
                                name="coinA_adjusted"
                                label="Your Coin A Holdings"
                                :value="old('coinA_adjusted', $input['coinA_adjusted'] ?? '')"
                                input-class="holdings-field"
                            >
                                Indicate the total amount of Coin A you currently possess for liquidity calculations (can be zero).
                            </x-input-field>
                        </div>
                        <div class="section">
                            <x-input-field
                                name="coinB_adjusted"
                                label="Your Coin B Holdings"
                                :value="old('coinB_adjusted', $input['coinB_adjusted'] ?? '')"
                                input-class="holdings-field"
                            >
                                Indicate the total amount of Coin B you currently possess for liquidity calculations (can be zero).
                            </x-input-field>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <h3 class="field-group__title">Slippage</h3>
                    <div class="field-row field-row--single">
                        <div class="section">
                            <x-input-field
                                name="slippage_pct"
                                label="Slippage buffer (%)"
                                :value="old('slippage_pct', $input['slippage_pct'] ?? '')"
                                :required="false"
                            >
                                Optional. Inflates buy amounts only (0–{{ $slippageMax }}%). Leave blank or 0 for ideal math.
                            </x-input-field>
                        </div>
                    </div>
                </div>

                <div class="section form-actions">
                    <button type="submit">Calculate</button>
                </div>
            </form>
        </section>

        <section class="ad-placeholder ad-placeholder--empty" aria-hidden="true">
            <!-- Ad content will be dynamically inserted here by Google Ads or other ad services -->
        </section>

        @if (isset($submitted) && isset($result))
            @include('partials.results-panel')
        @else
            <section class="results-container results-container--empty" id="results-placeholder" aria-live="polite">
                <h2>Your next moves</h2>
                <div class="empty-results-panel">
                    <span class="empty-results-mark" aria-hidden="true"></span>
                    <p class="empty-results">Your buy/sell plan will show up here after you calculate.</p>
                </div>
            </section>
        @endif

        <footer class="footer">
            <p class="disclaimer"><b>Disclaimer:</b> As you explore the ever-evolving DeFi landscape with
                our Liquidity Balancer, remember that all outputs are estimates, reflecting the fluidity of
                cryptocurrency markets. Our tool is not a source of financial advice and we are not liable for any
                inaccuracies in calculations or the decisions you make from them. We encourage users to proceed with
                care and make informed choices in their crypto dealings.
            </p>
        </footer>
    </div>
    <script src="{{ asset('js/calculate-mode.js') }}" defer></script>
</body>

</html>
