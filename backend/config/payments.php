<?php

return [
    'mock_enabled' => env('PAYMENT_MOCK_ENABLED', false),
    'sepay' => [
        'bank' => env('SEPAY_BANK'),
        'account_number' => env('SEPAY_ACCOUNT_NUMBER'),
        'account_name' => env('SEPAY_ACCOUNT_NAME'),
        'template' => env('SEPAY_QR_TEMPLATE', 'compact2'),
        'webhook_key' => env('SEPAY_WEBHOOK_KEY'),
    ],
];
