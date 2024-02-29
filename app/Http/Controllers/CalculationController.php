<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalculationController extends Controller {
    public function showForm() {
        return view('calculate');
    }

    public function performCalculation(Request $request) {
        // Validate input data
        $data = $request->validate([
            'coinA_initial' => 'required|numeric',
            'coinB_initial' => 'required|numeric',
            'coinA_price' => 'required|numeric',
            'coinB_price' => 'required|numeric',
            'coinA_adjusted' => 'required|numeric',
            'coinB_adjusted' => 'required|numeric',
        ]);

        $calcResults = $this->calculateResults($data);
        $text = $this->generateText($calcResults['totalValueAdjusted']);
        $text_swap = $this->generateSwapText($calcResults['unitsOfCoinsResult']);

        $return = [
            'submitted' => true,
            'input' => $request->all(),
            'text' => $text,
            'unitsOfCoinsResult' => $calcResults['unitsOfCoinsResult'],
            'text_swap' => $text_swap,
            'finalCoinA' => $calcResults['finalCoinA'],
            'finalCoinB' => $calcResults['finalCoinB'],
        ];

        // Returning results (can also be passed to a view)
        return view('calculate', $return);
    }

    private function calculateResults($data) {
        // Extracting values for ease of use
        $coinA_initial = $data['coinA_initial'];
        $coinB_initial = $data['coinB_initial'];
        $coinA_price = $data['coinA_price'];
        $coinB_price = $data['coinB_price'];
        $coinA_adjusted = $data['coinA_adjusted'];
        $coinB_adjusted = $data['coinB_adjusted'];

        // Calculations
        $initialProportionA = ($coinA_initial * $coinA_price) / (($coinA_initial * $coinA_price) + ($coinB_initial * $coinB_price));
        $adjustedValueA = $coinA_adjusted * $coinA_price;
        $adjustedValueB = $coinB_adjusted * $coinB_price;
        $totalValueAdjusted = $adjustedValueA + $adjustedValueB;
        $targetValueA = $totalValueAdjusted * $initialProportionA;
        $targetValueB = $totalValueAdjusted * (1 - $initialProportionA);
        $unitsOfCoinAToSell = ($adjustedValueA - $targetValueA) / $coinA_price;
        $unitsOfCoinBToSell = ($adjustedValueB - $targetValueB) / $coinB_price;

        // Final units post-rebalance
        $finalCoinA = $coinA_adjusted - $unitsOfCoinAToSell;
        $finalCoinB = $coinB_adjusted - $unitsOfCoinBToSell;

        // Units of coins to buy/sell
        $unitsOfCoins = [
            'A' => $unitsOfCoinAToSell,
            'B' => $unitsOfCoinBToSell
        ];
        $unitsOfCoinsResult = [];
        foreach ($unitsOfCoins as $key => $unitsOfCoin) {
            $unitsOfCoinsResult[$key] = [
                'text' => $unitsOfCoin < 0 ? 'buy' : 'sell',
                'amount' => abs($unitsOfCoin)
            ];
        }

        return [
            'totalValueAdjusted' => $totalValueAdjusted,
            'unitsOfCoinsResult' => $unitsOfCoinsResult,
            'unitsOfCoinAToSell' => $unitsOfCoinAToSell,
            'unitsOfCoinBToSell' => $unitsOfCoinBToSell,
            'finalCoinA' => $finalCoinA,
            'finalCoinB' => $finalCoinB
        ];
    }

    private function generateText($totalValue) {
        $rangeTexts  = [
            100 => "Hey, little shrimp 🦐 in the crypto sea, you're so tiny, a goldfish's portfolio looks like a whale's next to yours. But chin up, at least you're not crypto plankton – they're just the background noise for your minuscule trades. Remember, even a single Bitcoin is a myth in your world. Keep dreaming small, maybe one day you'll afford a fraction of a fraction!",
            1000 => "Ah, a crypto fish 🐠, bigger than a shrimp but still dreaming of being a dolphin. You've got a bit more coin, enough to not totally embarrass yourself on a forum. But let's face it, you're the ones the dolphins snack on when Bitcoin dips. Keep swimming, fishy, maybe one day you'll make it to the kiddie pool of the crypto ocean! ",
            10000 => "Look at you, a dolphin 🐬 in the crypto sea! A shrimp that hit the jackpot or just got lucky on a meme coin. You're playing with bigger stakes but still a splash away from being whale bait. Keep showing off those jumps, but remember, in the eyes of the whales, you're just a slightly bulkier fish with a college fund.",
            100000 => "A shark 🦈, huh? You're in the big leagues but not quite a whale. You think you're the predator, but let's be honest, you're just a bigger target for the whales. You've got some bite with your crypto stack, but in the crypto ocean, there's always a bigger fish. Keep hunting, but watch your fins, the whales don't play fair.",
        ];

        $text = "The mighty whale 🐳, king of the crypto ocean! You're swimming in crypto like it's your personal playground. But don't get too comfy; even whales can beach themselves. You're not just making waves, you're causing tsunamis in the market. Just remember, every whale has its day, but even you can't control the crypto weather. Stay afloat, big guy, or you'll sink like the Titanic";
        foreach ($rangeTexts as $upperLimit => $message) {
            if ($totalValue <= $upperLimit) {
                $text = $message;
                break;
            }
        }

        return $text;
    }

    private function generateSwapText($unitsOfCoinsResult) {
        $text_swap = '';

        if ($unitsOfCoinsResult['A']['text'] == 'sell') {
            $text_swap = "<p class='instructions'><strong>No more fooling around. Simply go to <a href='https://swap.defillama.com' class='link'>Llamaswap</a>, swap <em>" . round($unitsOfCoinsResult['A']['amount'], 6) . "</em> of coin A for <em>" . round($unitsOfCoinsResult['B']['amount'], 6) . "</em> of coin B on the correct blockchain. After the swap, then add the liquidity to your preferred DEX</strong></p>";
        } else {
            $text_swap = "<p class='instructions'><strong>No more fooling around. Simply go to <a href='https://swap.defillama.com' class='link'>Llamaswap</a>, swap <em>" . round($unitsOfCoinsResult['B']['amount'], 6) . "</em> of coin B for <em>" . round($unitsOfCoinsResult['A']['amount'], 6) . "</em> of coin A on the correct blockchain. After the swap, then add the liquidity to your preferred DEX</strong></p>";
        }

        return $text_swap;
    }
}
