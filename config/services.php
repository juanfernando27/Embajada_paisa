<?php

$appleKey = env('APPLE_PRIVATE_KEY_BASE64')
    ? base64_decode((string) env('APPLE_PRIVATE_KEY_BASE64'), true)
    : env('APPLE_PRIVATE_KEY_PATH');
$appleSecret = env('APPLE_CLIENT_SECRET');

return [
    'google' => [
        'enabled' => filled(env('GOOGLE_CLIENT_ID')) && filled(env('GOOGLE_CLIENT_SECRET')),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/auth/google/callback',
    ],

    'apple' => [
        'enabled' => filled(env('APPLE_CLIENT_ID')) && (filled($appleSecret)
            || (filled($appleKey) && filled(env('APPLE_TEAM_ID')) && filled(env('APPLE_KEY_ID')))),
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => $appleSecret ?: '',
        'redirect' => env('APPLE_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/auth/apple/callback',
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => $appleKey ?: '',
    ],


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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
