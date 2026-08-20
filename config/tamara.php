<?php

return [
    'enabled' => env('TAMARA_ENABLED', true),
    'environment' => env('TAMARA_ENVIRONMENT', 'sandbox'),
    'api_url' => env('TAMARA_API_URL'),
    'api_token' => env('TAMARA_API_TOKEN'),
    'notification_token' => env('TAMARA_NOTIFICATION_TOKEN'),
    'public_key' => env('TAMARA_PUBLIC_KEY'),
    'timeout' => (int) env('TAMARA_TIMEOUT', 15),
    'webhook' => [
        'enabled' => env('TAMARA_WEBHOOK_ENABLED', true),
        'path' => env('TAMARA_WEBHOOK_PATH', 'webhooks/tamara'),
        'middleware' => ['api'],
        'verify' => env('TAMARA_WEBHOOK_VERIFY', true),
        'idempotency' => env('TAMARA_WEBHOOK_IDEMPOTENCY', true),
        'idempotency_ttl' => (int) env('TAMARA_WEBHOOK_IDEMPOTENCY_TTL', 604800),
    ],
];
