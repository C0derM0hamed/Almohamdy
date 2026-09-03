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

    'sadq' => [
        'enabled' => (bool) env('SADQ_ENABLED', false),
        'base_url' => rtrim((string) env('SADQ_BASE_URL', 'https://apigw.sadq.sa'), '/'),
        'client_username' => (string) env('SADQ_CLIENT_USERNAME', ''),
        'client_password' => (string) env('SADQ_CLIENT_PASSWORD', ''),
        'auth_basic' => (string) env('SADQ_AUTH_BASIC', ''),
        'integration_username' => (string) env('SADQ_INTEGRATION_USERNAME', ''),
        'integration_password' => (string) env('SADQ_INTEGRATION_PASSWORD', ''),
        'account_id' => (string) env('SADQ_ACCOUNT_ID', ''),
        'account_secret' => (string) env('SADQ_ACCOUNT_SECRET', ''),
        'available_to' => (string) env('SADQ_AVAILABLE_TO', '2029-08-29'),
        'callback_secret' => (string) env('SADQ_CALLBACK_SECRET', ''),
        'timeout' => (int) env('SADQ_TIMEOUT', 60),
    ],

    'yakeen' => [
        'enabled' => (bool) env('YAQEEN_ENABLED', false),
        'base_url' => rtrim((string) env('YAQEEN_BASE_URL', 'https://yakeencore.api.elm.sa'), '/'),
        'username' => (string) env('YAQEEN_USERNAME', ''),
        'password' => (string) env('YAQEEN_PASSWORD', ''),
        'usage_code' => (string) env('YAQEEN_USAGE_CODE', ''),
        'operator_id' => (string) env('YAQEEN_OPERATOR_ID', ''),
        'app_id' => (string) env('YAQEEN_APP_ID', ''),
        'app_key' => (string) env('YAQEEN_APP_KEY', ''),
        'saudi_nationality_code' => (int) env('YAQEEN_SAUDI_NATIONALITY_CODE', 113),
        'timeout' => (int) env('YAQEEN_TIMEOUT', 30),
    ],

];
