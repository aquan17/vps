<?php

return [
    'bank_id' => env('DEPOSIT_BANK_ID', 'BIDV'),
    'account_no' => env('DEPOSIT_ACCOUNT_NO', '0862579104'),
    'account_name' => env('DEPOSIT_ACCOUNT_NAME', 'NGUYEN ANH QUAN'),
    'webhook_secret' => env('DEPOSIT_WEBHOOK_SECRET'),
    'provider' => env('DEPOSIT_PROVIDER', 'payos'),
    'payos' => [
        'client_id' => env('PAYOS_CLIENT_ID'),
        'api_key' => env('PAYOS_API_KEY'),
        'checksum_key' => env('PAYOS_CHECKSUM_KEY'),
        'base_url' => env('PAYOS_BASE_URL', 'https://api-merchant.payos.vn'),
    ],
];
