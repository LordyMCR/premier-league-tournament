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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'football_data' => [
        'api_key' => env('FOOTBALL_DATA_API_KEY'),
    ],

    'news_api' => [
        'api_key' => env('NEWS_API_KEY'),
    ],

    'football_api' => [
        'api_key' => env('FOOTBALL_API_KEY'), // APIFootball.com (free)
    ],

    'sports_db' => [
        'api_key' => env('SPORTS_DB_API_KEY', '123'), // TheSportsDB (free)
    ],

];
