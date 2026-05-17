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

    /*
    |--------------------------------------------------------------------------
    | Beem Africa SMS Service
    |--------------------------------------------------------------------------
    |
    | Configuration for Beem Africa SMS service integration
    |
    */

    'beem' => [
        'api_key' => env('BEEM_API_KEY'),
        'secret_key' => env('BEEM_SECRET_KEY'),
        'sender_id' => env('BEEM_SENDER_ID', 'PHIDTECH'),
        'base_url' => 'https://apisms.beem.africa/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Selcom Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Configuration for Selcom payment integration with TILL number support
    |
    */

    'selcom' => [
        'api_key' => env('SELCOM_API_KEY'),
        'api_secret' => env('SELCOM_API_SECRET'),
        'merchant_id' => env('SELCOM_MERCHANT_ID'),
        'till_number' => env('SELCOM_TILL_NUMBER'),
        'base_url' => env('SELCOM_BASE_URL', 'https://apigw.selcommobile.com'),
        'callback_url' => env('SELCOM_CALLBACK_URL', url('/webhooks/selcom/repayments')),
        'webhook_url' => env('SELCOM_WEBHOOK_URL', url('/webhooks/selcom/repayments')),
        'currency' => env('SELCOM_CURRENCY', 'TZS'),
        'environment' => env('SELCOM_ENVIRONMENT', 'sandbox'), // sandbox or production
        'timeout' => env('SELCOM_TIMEOUT', 30),
        'enabled' => env('SELCOM_ENABLED', false),
    ],

    // Admin notification routing
    'admin_email' => env('ADMIN_EMAIL', 'phidtechnology@gmail.com'),
    'notify_admin_all' => env('NOTIFY_ADMIN_ALL', true),

];