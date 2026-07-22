<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Email system configuration, mail driver, queue, and retry mechanism.
    |
    */

    'mail' => [
        /*
         * Email sending mode
         *
         * Available modes: "mailtrap", "smtp"
         * - mailtrap: Uses Mailtrap API for sending emails (recommended for production)
         * - smtp: Uses Laravel default mail driver (SMTP)
         */
        'mode' => env('MAILER_MODE', 'mailtrap'),

        /*
         * Mailtrap configuration (only for mailtrap mode)
         */
        'mailtrap' => [
            'api_key' => env('MAILTRAP_API_KEY'),
            'use_sandbox' => env('MAILTRAP_USE_SANDBOX', true),
            'inbox_id' => env('MAILTRAP_INBOX_ID'),
        ],

        /*
         * Queue configuration for email processing
         * Uses QUEUE_CONNECTION from Laravel environment
         */
        'queue' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'name' => env('MAILER_QUEUE_NAME', 'email'),
        ],

        /*
         * Retry configuration for failed email jobs
         */
        'retry' => [
            'max_attempts' => env('MAILER_MAX_ATTEMPTS', 3),
            'backoff' => [10, 30, 60],
            'timeout' => env('MAILER_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Uses LOG_CHANNEL from environment for centralized logging.
    | Default: 'stack' (follows config/logging.php)
    |
    */

    'log' => [
        'channel' => env('LOG_CHANNEL', 'stack'),
    ],

];