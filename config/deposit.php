<?php

return [
    'bank_id' => env('DEPOSIT_BANK_ID', 'BIDV'),
    'account_no' => env('DEPOSIT_ACCOUNT_NO', '0862579104'),
    'account_name' => env('DEPOSIT_ACCOUNT_NAME', 'NGUYEN ANH QUAN'),
    'webhook_secret' => env('DEPOSIT_WEBHOOK_SECRET'),
];
