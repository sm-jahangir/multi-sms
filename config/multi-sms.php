<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default SMS driver that will be used to send
    | messages. You may set this to any of the drivers defined below.
    |
    */
    'default' => env('SMS_DRIVER', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the SMS drivers for your application. Each driver
    | has its own configuration options.
    |
    */
    'drivers' => [
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],

        'vonage' => [
            'api_key' => env('VONAGE_API_KEY'),
            'api_secret' => env('VONAGE_API_SECRET'),
            'from' => env('VONAGE_FROM'),
        ],

        'plivo' => [
            'auth_id' => env('PLIVO_AUTH_ID'),
            'auth_token' => env('PLIVO_AUTH_TOKEN'),
            'from' => env('PLIVO_FROM'),
        ],

        'infobip' => [
            'api_key' => env('INFOBIP_API_KEY'),
            'base_url' => env('INFOBIP_BASE_URL', 'https://api.infobip.com'),
            'from' => env('INFOBIP_FROM'),
        ],

        'messagebird' => [
            'access_key' => env('MESSAGEBIRD_ACCESS_KEY'),
            'from' => env('MESSAGEBIRD_FROM'),
        ],

        'viber' => [
            'auth_token' => env('VIBER_AUTH_TOKEN'),
            'sender_name' => env('VIBER_SENDER_NAME'),
        ],

        'whatsapp' => [
            'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'from' => env('WHATSAPP_FROM'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Priority
    |--------------------------------------------------------------------------
    |
    | Define the order of drivers to try if the primary driver fails.
    |
    */
    'fallback_priority' => [
        'twilio',
        'vonage',
        'plivo',
        'infobip',
        'messagebird',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sender Number
    |--------------------------------------------------------------------------
    |
    | The default phone number to use when sending SMS messages.
    |
    */
    'default_from' => env('SMS_DEFAULT_FROM'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging of SMS messages.
    |
    */
    'logging' => [
        'enabled' => env('SMS_LOGGING_ENABLED', true),
        'log_success' => env('SMS_LOG_SUCCESS', true),
        'log_failures' => env('SMS_LOG_FAILURES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Enable or disable SMS scheduling functionality.
    |
    */
    'scheduling' => [
        'enabled' => env('SMS_SCHEDULING_ENABLED', true),
        'queue' => env('SMS_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for SMS sending.
    |
    */
    'rate_limiting' => [
        'enabled' => env('SMS_RATE_LIMITING_ENABLED', false),
        'max_attempts' => env('SMS_MAX_ATTEMPTS', 60),
        'decay_minutes' => env('SMS_DECAY_MINUTES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry attempts for failed SMS messages.
    |
    */
    'retry' => [
        'max_attempts' => env('SMS_RETRY_MAX_ATTEMPTS', 3),
        'delay_seconds' => env('SMS_RETRY_DELAY_SECONDS', 60),
    ],
];