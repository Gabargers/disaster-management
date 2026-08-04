<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'system_a' => [
        'api_token' => env('SYSTEM_A_API_TOKEN'),
        'rate_limit_per_minute' => env('SYSTEM_A_RATE_LIMIT_PER_MINUTE', 60),
        'rate_limit_burst_per_second' => env('SYSTEM_A_RATE_LIMIT_BURST_PER_SECOND', 10),
        'max_body_bytes' => env('SYSTEM_A_MAX_BODY_BYTES', 262144),
        'clock_skew_seconds' => env('SYSTEM_A_CLOCK_SKEW_SECONDS', 300),
    ],

];
