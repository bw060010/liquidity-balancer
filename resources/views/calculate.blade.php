<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Liquidity Balancer Calculator | Optimize LP Deposits - Liquidity-Balancer.com</title>
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
</head>

<body>
    <div class="grid-container">
        <header class="header">
            <div class="logo-container">
                @include('partials.logo')
            </div>
            <div class="text-container">
                <h1>Liquidity Balancer Calculator: Streamline Your DeFi Pool Deposits</h1>
                <p class="info">Discover our Liquidity Balancer Calculator: the essential, free tool for optimizing
                    your LP deposits in a cryptocurrency DEX. Crafted for precise coin balancing, it
                    simplifies deposits for both new and experienced stakers. Enter your coin amounts and
                    instantly find your ideal investment balance.
                </p>
            </div>
        </header>

        <section class="form-container">
            <h2>Input</h2>
            <form action="{{ route('calculate.store') }}#ad-placeholder" method="POST" class="form">
                @csrf
                <div class="section">
                    <x-input-field
                        name="coinA_initial"
                        label="Coin A Reference Amount"
                        :value="old('coinA_initial', $input['coinA_initial'] ?? '1')"
                        :readonly="true"
                    >
                        Fixed at 1 for Coin A to standardize comparison against Coin B
                    </x-input-field>
                </div>
                <div class="section">
                    <x-input-field
                        name="coinB_initial"
                        label="Coin B Equivalent Amount"
                        :value="old('coinB_initial', $input['coinB_initial'] ?? '')"
                    >
                        Enter the value of Coin B equivalent to 1 unit of Coin A according to the latest DEX rates
                        <img src="{{ asset('images/tooltips/example_coin_b_equivalent_amount.png') }}"
                            alt="Example Coin B Equivalent Amount">
                    </x-input-field>
                </div>
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
                        Enter the latest market price of Coin B (find on CoinGecko or similar). A.
                    </x-input-field>
                </div>
                <div class="section">
                    <x-input-field
                        name="coinA_adjusted"
                        label="Your Coin A Holdings"
                        :value="old('coinA_adjusted', $input['coinA_adjusted'] ?? '')"
                    >
                        Indicate the total amount of Coin A you currently possess for liquidity calculations (can be zero).
                    </x-input-field>
                </div>
                <div class="section">
                    <x-input-field
                        name="coinB_adjusted"
                        label="Your Coin B Holdings"
                        :value="old('coinB_adjusted', $input['coinB_adjusted'] ?? '')"
                    >
                        Indicate the total amount of Coin B you currently possess for liquidity calculations (can be zero).
                    </x-input-field>
                </div>
                <div class="section">
                    <button type="submit">Calculate</button>
                </div>
            </form>
        </section>

        <section class="ad-placeholder" @if (isset($submitted)) id="ad-placeholder" @endif>
            <!-- Ad content will be dynamically inserted here by Google Ads or other ad services -->
        </section>

        @if (isset($unitsOfCoinsResult))
            <section class="results-container">
                <h2>Output</h2>
                <p class="fun-text">{{ $text }}</p>
                @include('partials.swap-instructions', ['unitsOfCoinsResult' => $unitsOfCoinsResult])
                <div id="results" class="results">
                    <p><strong>Breakdown of the calculation:</strong><br>
                        Amount of coin A to {{ $unitsOfCoinsResult['A']['text'] }}:
                        <em>{{ $unitsOfCoinsResult['A']['amount'] }}</em><br>
                        Amount of coin B to {{ $unitsOfCoinsResult['B']['text'] }}:
                        <em>{{ $unitsOfCoinsResult['B']['amount'] }}</em><br>
                        Final coin A amount: <em>{{ $finalCoinA }}</em><br>
                        Final coin B amount: <em>{{ $finalCoinB }}</em>
                    </p>
                </div>
            </section>
        @endif
        <footer class="footer">
            <p class="disclaimer"><b>Disclaimer:</b> As you explore the ever-evolving DeFi landscape with
                our Liquidity Balancer, remember that all outputs are estimates, reflecting the fluidity of
                cryptocurrency markets. Our tool is not a source of financial advice and we are not liable for any
                inaccuracies in calculations or the decisions you make from them. We encourage users to proceed with
                care and make informed choices in their crypto dealings
            </p>
        </footer>
    </div>
</body>

</html>
