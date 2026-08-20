# Laravel Tamara

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
![License](https://img.shields.io/badge/license-MIT-blue.svg)

A strongly typed Laravel integration for [Tamara](https://tamara.co/) online checkout, order management, payment capture, and webhook notifications.

## Features

- Laravel auto-discovery and a convenient `Tamara` facade
- Typed builders and DTOs for customers, items, addresses, and orders
- Checkout sessions, payment types, order retrieval, and authorisation
- Full and partial capture with shipping information and fulfilled items
- HS256 webhook signature verification and typed Laravel events
- Atomic webhook deduplication through Laravel's cache
- Typed validation and API exceptions

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- A Tamara merchant account with sandbox or production credentials

## Laravel compatibility

| Laravel | PHP | Status |
| --- | --- | --- |
| 11.x | 8.2+ | Supported |
| 12.x | 8.2+ | Supported |
| 13.x | 8.3+ | Supported |

## Installation

```bash
composer require yasserelgammal/laravel-tamara
php artisan vendor:publish --tag=tamara-config
```

## Configuration

Add your Tamara credentials to `.env`:

```dotenv
TAMARA_ENABLED=true
TAMARA_ENVIRONMENT=sandbox
TAMARA_API_URL=https://api-sandbox.tamara.co
TAMARA_API_TOKEN=
TAMARA_NOTIFICATION_TOKEN=
TAMARA_PUBLIC_KEY=
TAMARA_TIMEOUT=15

TAMARA_WEBHOOK_ENABLED=true
TAMARA_WEBHOOK_PATH=webhooks/tamara
TAMARA_WEBHOOK_VERIFY=true
TAMARA_WEBHOOK_IDEMPOTENCY=true
TAMARA_WEBHOOK_IDEMPOTENCY_TTL=604800
```

If `TAMARA_API_URL` is empty, the package selects the URL from `TAMARA_ENVIRONMENT`:

| Environment | Base URL |
| --- | --- |
| Sandbox | `https://api-sandbox.tamara.co` |
| Production | `https://api.tamara.co` |

Keep the API and notification tokens on your backend. Never expose them to a browser or mobile application.

```php
use YasserElgammal\Tamara\Facades\Tamara;

if (! Tamara::isConfigured()) {
    // Tamara is disabled or the API token is missing.
}
```

## Create a checkout session

```php
use YasserElgammal\Tamara\DTOs\TamaraAddressData;
use YasserElgammal\Tamara\Facades\Tamara;

$customer = Tamara::customer()
    ->firstName($user->first_name)
    ->lastName($user->last_name)
    ->email($user->email)
    ->phone($user->full_phone)
    ->build();

$shippingAddress = new TamaraAddressData(
    firstName: $user->first_name,
    lastName: $user->last_name,
    line1: $order->shipping_address,
    city: $order->shipping_city,
    countryCode: 'SA',
    phone: $user->full_phone,
);

$items = $order->items->map(fn ($orderItem) => Tamara::item()
    ->name($orderItem->product_name)
    ->referenceId((string) $orderItem->product_id)
    ->sku((string) $orderItem->sku)
    ->type('Physical')
    ->quantity($orderItem->quantity)
    ->unitPrice($orderItem->unit_price)
    ->currency('SAR')
    ->build());

$tamaraOrder = Tamara::order()
    ->referenceId('ORD-'.$order->id)
    ->orderNumber((string) $order->number)
    ->description('Order '.$order->number)
    ->countryCode('SA')
    ->currency('SAR')
    ->total($order->final_total)
    ->customer($customer)
    ->shippingAddress($shippingAddress)
    ->items($items)
    ->merchantUrls(
        route('payments.tamara.success'),
        route('payments.tamara.failure'),
        route('payments.tamara.cancel'),
    )
    ->build();

$checkout = Tamara::orders()->create($tamaraOrder);

// Persist these identifiers before redirecting the customer.
$order->update([
    'tamara_order_id' => $checkout->orderId,
    'tamara_checkout_id' => $checkout->checkoutId,
]);

return redirect()->away($checkout->url());
```

The order `total()` must equal the final amount charged. Each item's `unitPrice()` is the price of one unit. For one item, use `item($item)` instead of `items($items)`.

You can also map any iterable with `itemsFrom()`:

```php
->itemsFrom($order->items, fn ($orderItem) => Tamara::item()
    ->name($orderItem->product_name)
    ->referenceId((string) $orderItem->product_id)
    ->quantity($orderItem->quantity)
    ->unitPrice($orderItem->unit_price)
    ->build())
```

## Mobile checkout response

Return checkout details from your backend instead of redirecting:

```php
$checkout = Tamara::orders()->create($tamaraOrder);

return response()->json(
    $checkout->sdkPayload(config('tamara.public_key'))
);
```

This package handles the backend integration only. It does not contain Android, iOS, or Flutter code.

## Order operations

```php
$order = Tamara::orders()->get($tamaraOrderId);

$paymentTypes = Tamara::orders()->paymentTypes(
    country: 'SA',
    value: 250,
    currency: 'SAR',
    phone: $customerPhone,
);

$authorisedOrder = Tamara::orders()->authorise($tamaraOrderId);
```

Authorise an approved order after receiving `order_approved`, unless auto-authorisation is enabled for your Tamara account.

## Capture a payment

Capture an authorised payment when the order is shipped or fulfilled. Tamara requires shipping information; include fulfilled items for full or partial capture:

```php
use DateTimeImmutable;
use YasserElgammal\Tamara\DTOs\TamaraShippingInfoData;

$shippingInfo = new TamaraShippingInfoData(
    shippedAt: new DateTimeImmutable(),
    shippingCompany: 'DHL',
    trackingNumber: 'TRACK-100',
    trackingUrl: 'https://shipping.example/track/TRACK-100',
);

$capture = Tamara::payments()->capture(
    orderId: $tamaraOrderId,
    amount: 250,
    shippingInfo: $shippingInfo,
    currency: 'SAR',
    items: $tamaraOrder->items,
);
```

Do not ship based only on the browser success redirect. Use the verified webhook and remote order status as the source of truth.

## Webhooks

Tamara webhooks are the source of truth for payment status changes. Do not mark an order as paid from the success redirect alone.

### 1. Configure the endpoint

The package registers this route when `TAMARA_WEBHOOK_ENABLED=true`:

```text
POST /webhooks/tamara
```

Confirm that Laravel loaded it:

```bash
php artisan route:list --name=tamara.webhook
```

Set the notification token supplied by Tamara (not the API token) in `.env`:

```dotenv
TAMARA_NOTIFICATION_TOKEN=your-notification-token
TAMARA_WEBHOOK_ENABLED=true
TAMARA_WEBHOOK_PATH=webhooks/tamara
TAMARA_WEBHOOK_VERIFY=true
TAMARA_WEBHOOK_IDEMPOTENCY=true
TAMARA_WEBHOOK_IDEMPOTENCY_TTL=604800
```

After changing environment values, clear cached configuration:

```bash
php artisan config:clear
```

The default route uses Laravel's `api` middleware and therefore does not require a CSRF exception. If you replace it with the `web` middleware, exclude the webhook path from CSRF validation.

The route belongs to the package. Do **not** define another `POST /webhooks/tamara` route or create a second webhook controller in the host application. Laravel package auto-discovery loads `TamaraServiceProvider`, and the provider registers the route from the package automatically. Your application only needs to listen for the events dispatched by that controller.

### 2. Register the webhook in Tamara

In the Tamara Partner Portal, register the public HTTPS URL:

```text
https://your-domain.com/webhooks/tamara
```

Select the order events your application handles. At minimum, subscribe to `order_approved`; subscribing to all supported order events is recommended so local and remote states remain synchronized.

Tamara sends a signed JWT in the `Authorization: Bearer` header and as the `tamaraToken` query parameter. The package verifies its HS256 signature using `TAMARA_NOTIFICATION_TOKEN` before parsing or dispatching the notification. Never disable verification in production.

### 3. Handle Laravel events

Each accepted Tamara event is converted into a typed Laravel event:

| Tamara event | Laravel event |
| --- | --- |
| `order_approved` | `TamaraOrderApproved` |
| `order_declined` | `TamaraOrderDeclined` |
| `order_authorised` | `TamaraOrderAuthorised` |
| `order_canceled` | `TamaraOrderCanceled` |
| `order_captured` | `TamaraPaymentCaptured` |
| `order_refunded` | `TamaraRefundCreated` |
| `order_expired` | `TamaraOrderExpired` |

Create a listener in the host application:

```bash
php artisan make:listener AuthoriseTamaraOrder
```

Register it in `App\Providers\AppServiceProvider::boot()` (or use your application's event discovery):

```php
use Illuminate\Support\Facades\Event;
use App\Listeners\AuthoriseTamaraOrder;
use YasserElgammal\Tamara\Events\TamaraOrderApproved;

public function boot(): void
{
    Event::listen(TamaraOrderApproved::class, AuthoriseTamaraOrder::class);
}
```

Then implement the listener. Queue webhook work so Tamara receives a fast response:

```php
<?php

namespace App\Listeners;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use YasserElgammal\Tamara\Events\TamaraOrderApproved;
use YasserElgammal\Tamara\Facades\Tamara;

final class AuthoriseTamaraOrder implements ShouldQueue
{
    public function handle(TamaraOrderApproved $event): void
    {
        $order = Order::query()
            ->where('tamara_order_id', $event->orderId())
            ->where('gateway_reference', $event->referenceId())
            ->firstOrFail();

        // Make this operation idempotent in your own database as well.
        if ($order->payment_status === 'authorised') {
            return;
        }

        $remoteOrder = Tamara::orders()->get($event->orderId());

        if (($remoteOrder['status'] ?? null) !== 'approved') {
            return;
        }

        $result = Tamara::orders()->authorise($event->orderId());

        $order->update([
            'payment_status' => $result['status'] ?? 'authorised',
        ]);
    }
}
```

Run a queue worker when using queued listeners:

```bash
php artisan queue:work
```

Do not capture the payment in the `order_approved` listener unless the order has already been fulfilled. Authorise on approval, then call `Tamara::payments()->capture(...)` when the product is shipped or the digital service is delivered.

### Recommended single-listener integration

Applications that keep all Tamara state transitions in one place can map every typed event to one listener. Create it first:

```bash
php artisan make:listener ProcessTamaraWebhook
```

Register the mappings in `app/Providers/EventServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Listeners\ProcessTamaraWebhook;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use YasserElgammal\Tamara\Events\TamaraOrderApproved;
use YasserElgammal\Tamara\Events\TamaraOrderAuthorised;
use YasserElgammal\Tamara\Events\TamaraOrderCanceled;
use YasserElgammal\Tamara\Events\TamaraOrderDeclined;
use YasserElgammal\Tamara\Events\TamaraOrderExpired;
use YasserElgammal\Tamara\Events\TamaraPaymentCaptured;
use YasserElgammal\Tamara\Events\TamaraRefundCreated;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TamaraOrderApproved::class => [ProcessTamaraWebhook::class],
        TamaraOrderAuthorised::class => [ProcessTamaraWebhook::class],
        TamaraPaymentCaptured::class => [ProcessTamaraWebhook::class],
        TamaraOrderDeclined::class => [ProcessTamaraWebhook::class],
        TamaraOrderCanceled::class => [ProcessTamaraWebhook::class],
        TamaraOrderExpired::class => [ProcessTamaraWebhook::class],
        TamaraRefundCreated::class => [ProcessTamaraWebhook::class],
    ];
}
```

On Laravel 11 and newer, make sure a custom event provider is listed in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
];
```

The listener can accept the common parent event and branch on the typed enum:

```php
<?php

namespace App\Listeners;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use YasserElgammal\Tamara\Enums\WebhookEventType;
use YasserElgammal\Tamara\Events\TamaraWebhookReceived;
use YasserElgammal\Tamara\Facades\Tamara;

final class ProcessTamaraWebhook implements ShouldQueue
{
    public int $tries = 5;

    public function handle(TamaraWebhookReceived $event): void
    {
        $localOrder = Order::query()
            ->where('tamara_order_id', $event->orderId())
            ->where('gateway_reference', $event->referenceId())
            ->lockForUpdate()
            ->firstOrFail();

        // Persist processed event keys and return early on queue retries.
        if ($localOrder->hasProcessedTamaraEvent($event->webhook->idempotencyKey())) {
            return;
        }

        match ($event->webhook->type) {
            WebhookEventType::APPROVED => Tamara::orders()->authorise($event->orderId()),
            WebhookEventType::AUTHORISED => $this->captureDeliveredOrder($localOrder, $event),
            WebhookEventType::CAPTURED => $localOrder->markAsPaid(),
            WebhookEventType::DECLINED => $localOrder->markAsFailed(),
            WebhookEventType::CANCELED,
            WebhookEventType::EXPIRED => $localOrder->markAsCanceled(),
            WebhookEventType::REFUNDED => $localOrder->markAsRefunded(),
        };

        $localOrder->recordProcessedTamaraEvent($event->webhook->idempotencyKey());
    }
}
```

The model and listener methods above are application-specific placeholders: implement them using a database transaction and your own payment/order schema. Always make local processing idempotent even though the package also suppresses duplicate deliveries in cache. For physical goods, capture only after fulfilment; for an immediately delivered digital product, capturing from the authorised-event workflow may be appropriate.

### Event data

Every typed event provides these helpers:

```php
$event->orderId();      // Tamara order ID
$event->referenceId();  // Merchant order_reference_id
$event->payload();      // Complete webhook payload
$event->webhook->type;  // WebhookEventType enum
$event->webhook->data;  // Event-specific capture/refund/cancel data
```

Always match both the Tamara order ID and your merchant reference before changing a local order.

### Responses and duplicate delivery

The package endpoint returns:

| Status | Meaning |
| --- | --- |
| `200` | Signature and payload are valid; the event was dispatched |
| `202` | A duplicate notification was accepted but not dispatched again |
| `401` | The notification token is missing or its signature is invalid |
| `422` | The payload is malformed or the event type is unsupported |

Duplicate notifications are suppressed for seven days by default. Use an atomic shared cache such as Redis in multi-server production. Your listeners should still be idempotent at the database level, because queue retries and deliberate reprocessing can execute application logic more than once.

### Testing webhooks

Use Tamara Sandbox to test a real signed webhook. A plain `curl` request without a valid Tamara JWT should return `401`, which confirms that signature verification is active:

```bash
curl -i -X POST https://your-domain.com/webhooks/tamara \
    -H "Content-Type: application/json" \
    -d '{"order_id":"test","order_reference_id":"test","event_type":"order_approved","data":{}}'
```

For local development, expose the application through an HTTPS tunnel, register that temporary URL in the Tamara Sandbox portal, and inspect `storage/logs/laravel.log` together with the queue worker output.

## Error handling

Invalid input throws `ValidationException`. Failed API requests throw `ApiException`, exposing the HTTP status and decoded response:

```php
use YasserElgammal\Tamara\Exceptions\ApiException;
use YasserElgammal\Tamara\Exceptions\ValidationException;

try {
    $checkout = Tamara::orders()->create($tamaraOrder);
} catch (ValidationException $exception) {
    report($exception);
} catch (ApiException $exception) {
    logger()->error('Tamara request failed', [
        'status' => $exception->status,
        'response' => $exception->response,
    ]);
}
```

Avoid logging credentials or complete customer payloads.

## Testing

```bash
composer install
vendor/bin/phpunit
```

The suite fakes Tamara HTTP requests and covers builders, checkout, payment types, authorisation, capture payloads, API failures, signed webhooks, event dispatch, and duplicate notifications.

Before production, complete this flow with Tamara sandbox credentials:

1. Create and complete a checkout session.
2. Receive and verify `order_approved`.
3. Authorise the order.
4. Capture it after fulfillment.
5. Confirm its final status through Tamara's API.

## Current scope

This release supports checkout creation, payment types, order retrieval, authorisation, capture, and webhooks. Cancel and refund API methods are not currently exposed.

## Security

Report security issues privately to the package maintainer instead of opening a public issue.

## License

Laravel Tamara is open-source software released under the [MIT License](LICENSE).
