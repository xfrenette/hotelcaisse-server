<?php

return [
    'deviceApprovals' => [
        'defaultLifetime' => 30 * 60, // In seconds
    ],
    'auth' => [
        'token' => [
            'bytesLength' => 32,
            'daysValid' => 180,
        ],
    ],
    'orders' => [
        'list' => [
            'quantity' => [
                'max' => 20,
            ],
        ],
    ],
];
