<?php

return [
    'auth' => [
        'token' => [
            'bytesLength' => 32,
            'daysValid' => 30,
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
