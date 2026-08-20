<?php

namespace YasserElgammal\Tamara\Webhooks;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use YasserElgammal\Tamara\DTOs\WebhookData;
use YasserElgammal\Tamara\Enums\WebhookEventType;
use YasserElgammal\Tamara\Events\{TamaraOrderApproved,TamaraOrderAuthorised,TamaraOrderCanceled,TamaraOrderDeclined,TamaraOrderExpired,TamaraPaymentCaptured,TamaraRefundCreated};

final class WebhookHandler
{
    public function __construct(private readonly Repository $cache,private readonly Dispatcher $events) {}
    public function handle(WebhookData $data): bool
    {
        $key='tamara:webhook:'.$data->idempotencyKey();
        if (config('tamara.webhook.idempotency',true) && ! $this->cache->add($key,true,(int)config('tamara.webhook.idempotency_ttl',604800))) return false;
        $class=match($data->type){WebhookEventType::APPROVED=>TamaraOrderApproved::class,WebhookEventType::DECLINED=>TamaraOrderDeclined::class,WebhookEventType::AUTHORISED=>TamaraOrderAuthorised::class,WebhookEventType::CANCELED=>TamaraOrderCanceled::class,WebhookEventType::CAPTURED=>TamaraPaymentCaptured::class,WebhookEventType::REFUNDED=>TamaraRefundCreated::class,WebhookEventType::EXPIRED=>TamaraOrderExpired::class};
        $this->events->dispatch(new $class($data)); return true;
    }
}
