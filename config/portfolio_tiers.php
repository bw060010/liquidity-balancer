<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Portfolio personality messages
    |--------------------------------------------------------------------------
    |
    | Upper-bound thresholds (inclusive) mapped to structured copy shown after
    | a calculation. Values above the last threshold use "default".
    |
    */
    'messages' => [
        100 => [
            'id' => 'shrimp',
            'label' => 'Shrimp',
            'emoji' => '🦐',
            'short' => 'Tiny bag, clear plan — that is how shrimp grow in the crypto sea.',
            'full' => 'Hey, little shrimp in the crypto sea: your stack is small, and that is fine. Even the whales started somewhere. Keep the plan tight, deposit clean, and give yourself room to grow.',
        ],
        1000 => [
            'id' => 'fish',
            'label' => 'Fish',
            'emoji' => '🐠',
            'short' => 'Past shrimp status — still swimming, still building.',
            'full' => 'A solid crypto fish: bigger than a shrimp, not yet a dolphin. You have enough to matter and enough to protect. Balance carefully, then add liquidity with intent.',
        ],
        10000 => [
            'id' => 'dolphin',
            'label' => 'Dolphin',
            'emoji' => '🐬',
            'short' => 'Nice splash — dolphin territory. Stay sharp on the ratio.',
            'full' => 'Look at you, a dolphin in the crypto sea. Bigger stakes, still plenty of ocean above you. Show off the clean deposit, not just the jumps — whales notice sloppy entries.',
        ],
        100000 => [
            'id' => 'shark',
            'label' => 'Shark',
            'emoji' => '🦈',
            'short' => 'Shark-sized stack. Hunt carefully; the pool does not forgive fuzzy math.',
            'full' => 'A shark, huh? Big leagues, not quite whale. You have bite — use it on precision, not pride. Check the weights, run the swap, then deposit like you mean it.',
        ],
    ],

    'default' => [
        'id' => 'whale',
        'label' => 'Whale',
        'emoji' => '🐳',
        'short' => 'Whale energy. Even the big ones balance before they deposit.',
        'full' => 'The mighty whale — deep pockets, bigger waves. Comfort is the trap: even whales beach themselves on a bad ratio. Stay precise, stay afloat, and let the plan do the heavy lifting.',
    ],
];
