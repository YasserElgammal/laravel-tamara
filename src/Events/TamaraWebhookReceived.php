<?php

namespace YasserElgammal\Tamara\Events;

use Illuminate\Foundation\Events\Dispatchable;
use YasserElgammal\Tamara\DTOs\WebhookData;

class TamaraWebhookReceived { use Dispatchable; public function __construct(public readonly WebhookData $webhook) {} public function orderId(): string{return $this->webhook->orderId;} public function referenceId(): string{return $this->webhook->referenceId;} /** @return array<string,mixed> */ public function payload():array{return $this->webhook->payload;} }
