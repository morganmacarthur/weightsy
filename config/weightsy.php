<?php

return [
    'checkin_address' => env('WEIGHTSY_CHECKIN_ADDRESS', 'checkin@weightsy.com'),
    'default_timezone' => env('WEIGHTSY_DEFAULT_TIMEZONE', 'America/Los_Angeles'),
    'imap' => [
        'host' => env('WEIGHTSY_IMAP_HOST', '127.0.0.1'),
        'port' => (int) env('WEIGHTSY_IMAP_PORT', 993),
        'encryption' => env('WEIGHTSY_IMAP_ENCRYPTION', 'ssl'),
        'mailbox' => env('WEIGHTSY_IMAP_MAILBOX', 'INBOX'),
        'username' => env('WEIGHTSY_IMAP_USERNAME'),
        'password' => env('WEIGHTSY_IMAP_PASSWORD'),
        'delete_after_processing' => (bool) env('WEIGHTSY_IMAP_DELETE_AFTER_PROCESSING', false),
    ],
    'signing' => [
        'minutes' => (int) env('WEIGHTSY_SIGNED_LINK_MINUTES', 10080),
    ],
    'postmark' => [
        'token' => env('WEIGHTSY_POSTMARK_TOKEN'),
        'from' => env('WEIGHTSY_POSTMARK_FROM', env('MAIL_FROM_ADDRESS', 'update@weightsy.com')),
    ],
];
