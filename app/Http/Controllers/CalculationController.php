<?php

namespace App\Http\Controllers;

use App\Services\LiquidityRebalancer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalculationController extends Controller
{
    public function __construct(private LiquidityRebalancer $rebalancer)
    {
    }

    public function showForm()
    {
        return view('calculate');
    }

    public function performCalculation(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(LiquidityRebalancer::MODES)],
            'coinA_initial' => 'required|numeric|gt:0',
            'coinB_initial' => 'required|numeric|gt:0',
            'coinA_price' => 'required|numeric|gt:0',
            'coinB_price' => 'required|numeric|gt:0',
            'coinA_adjusted' => 'nullable|numeric|gte:0',
            'coinB_adjusted' => 'nullable|numeric|gte:0',
            'new_capital' => 'required_if:mode,deploy_budget|nullable|numeric|gt:0',
            'slippage_pct' => 'nullable|numeric|min:0|max:5',
        ]);

        $data['coinA_adjusted'] = $data['coinA_adjusted'] ?? 0;
        $data['coinB_adjusted'] = $data['coinB_adjusted'] ?? 0;
        $data['slippage_pct'] = $data['slippage_pct'] ?? 0;

        $calcResults = $this->rebalancer->calculate($data);
        $text = $this->generateText($calcResults['totalValue']);
        $text_swap = $this->generateSwapText($calcResults['unitsOfCoinsResult']);

        return view('calculate', [
            'submitted' => true,
            'input' => $request->all(),
            'text' => $text,
            'unitsOfCoinsResult' => $calcResults['unitsOfCoinsResult'],
            'text_swap' => $text_swap,
            'finalCoinA' => $calcResults['finalCoinA'],
            'finalCoinB' => $calcResults['finalCoinB'],
            'mode' => $calcResults['mode'],
            'warnings' => $calcResults['warnings'],
            'capitalRequired' => $calcResults['capitalRequired'],
            'capitalDeployed' => $calcResults['capitalDeployed'],
            'slippageApplied' => $calcResults['slippageApplied'],
            'idealBuys' => $calcResults['idealBuys'],
            'totalValue' => $calcResults['totalValue'],
        ]);
    }

    private function generateText($totalValue)
    {
        $rangeTexts = [
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

    private function generateSwapText(array $unitsOfCoinsResult): string
    {
        $actionA = $unitsOfCoinsResult['A']['text'];
        $actionB = $unitsOfCoinsResult['B']['text'];
        $amountA = round($unitsOfCoinsResult['A']['amount'], 6);
        $amountB = round($unitsOfCoinsResult['B']['amount'], 6);

        $needsBuyA = $actionA === 'buy' && $amountA > 0;
        $needsBuyB = $actionB === 'buy' && $amountB > 0;
        $needsSellA = $actionA === 'sell' && $amountA > 0;
        $needsSellB = $actionB === 'sell' && $amountB > 0;

        if (!$needsBuyA && !$needsBuyB && !$needsSellA && !$needsSellB) {
            return "<p class='instructions'><strong>No swap needed. Your planned balances already match the pool ratio — add liquidity on your preferred DEX.</strong></p>";
        }

        if ($needsBuyA && $needsBuyB && !$needsSellA && !$needsSellB) {
            return "<p class='instructions'><strong>No more fooling around. Acquire <em>{$amountA}</em> of coin A and <em>{$amountB}</em> of coin B (for example via <a href='https://swap.defillama.com' class='link'>Llamaswap</a> on the correct blockchain — two buys or one routing path). After you have both sides, add the liquidity to your preferred DEX.</strong></p>";
        }

        if ($needsSellA && $needsBuyB) {
            return "<p class='instructions'><strong>No more fooling around. Simply go to <a href='https://swap.defillama.com' class='link'>Llamaswap</a>, swap <em>{$amountA}</em> of coin A for <em>{$amountB}</em> of coin B on the correct blockchain. After the swap, then add the liquidity to your preferred DEX</strong></p>";
        }

        if ($needsSellB && $needsBuyA) {
            return "<p class='instructions'><strong>No more fooling around. Simply go to <a href='https://swap.defillama.com' class='link'>Llamaswap</a>, swap <em>{$amountB}</em> of coin B for <em>{$amountA}</em> of coin A on the correct blockchain. After the swap, then add the liquidity to your preferred DEX</strong></p>";
        }

        if ($needsBuyA && !$needsBuyB && !$needsSellA && !$needsSellB) {
            return "<p class='instructions'><strong>No more fooling around. Simply go to <a href='https://swap.defillama.com' class='link'>Llamaswap</a>, buy <em>{$amountA}</em> of coin A on the correct blockchain. After the purchase, then add the liquidity to your preferred DEX</strong></p>";
        }

        if ($needsBuyB && !$needsBuyA && !$needsSellA && !$needsSellB) {
            return "<p class='instructions'><strong>No more fooling around. Simply go to <a href='https://swap.defillama.com' class='link'>Llamaswap</a>, buy <em>{$amountB}</em> of coin B on the correct blockchain. After the purchase, then add the liquidity to your preferred DEX</strong></p>";
        }

        return "<p class='instructions'><strong>Review the breakdown below, execute the listed buys/sells (for example via <a href='https://swap.defillama.com' class='link'>Llamaswap</a>), then add liquidity on your preferred DEX.</strong></p>";
    }
}
