<?php

use Illuminate\Support\Facades\Route;
use YasserElgammal\Tamara\Webhooks\WebhookController;

Route::middleware(config('tamara.webhook.middleware', ['api']))
    ->post(ltrim((string) config('tamara.webhook.path', 'webhooks/tamara'), '/'), WebhookController::class)
    ->name('tamara.webhook');
