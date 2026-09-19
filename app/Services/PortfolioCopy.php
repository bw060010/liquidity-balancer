<?php

namespace App\Services;

class PortfolioCopy
{
    /**
     * Return the portfolio-size personality payload for a given total adjusted value.
     *
     * @return array{id: string, label: string, emoji: string, short: string, full: string}
     */
    public function forTotalValue(float $totalValue): array
    {
        $tiers = config('portfolio_tiers.messages', []);

        foreach ($tiers as $upperLimit => $message) {
            if ($totalValue <= (float) $upperLimit) {
                return $this->normalize($message);
            }
        }

        return $this->normalize(config('portfolio_tiers.default', []));
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{id: string, label: string, emoji: string, short: string, full: string}
     */
    private function normalize(array $message): array
    {
        return [
            'id' => (string) ($message['id'] ?? 'unknown'),
            'label' => (string) ($message['label'] ?? 'Unknown'),
            'emoji' => (string) ($message['emoji'] ?? ''),
            'short' => (string) ($message['short'] ?? ''),
            'full' => (string) ($message['full'] ?? ''),
        ];
    }
}
