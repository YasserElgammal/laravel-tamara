<?php

namespace YasserElgammal\Tamara\DTOs;

use YasserElgammal\Tamara\Enums\WebhookEventType;
use YasserElgammal\Tamara\Exceptions\ValidationException;

final readonly class WebhookData
{
    /** @param array<string,mixed> $data @param array<string,mixed> $payload */
    public function __construct(public string $orderId,public string $referenceId,public ?string $orderNumber,public WebhookEventType $type,public array $data,public array $payload) {}
    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $id=$payload['order_id'] ?? null; $ref=$payload['order_reference_id'] ?? null; $type=WebhookEventType::tryFrom((string)($payload['event_type'] ?? ''));
        if (! is_string($id)||$id===''||!is_string($ref)||$ref===''||!$type) throw new ValidationException('Malformed or unsupported Tamara webhook payload.');
        return new self($id,$ref,isset($payload['order_number'])?(string)$payload['order_number']:null,$type,is_array($payload['data']??null)?$payload['data']:[],$payload);
    }
    public function idempotencyKey(): string { return hash('sha256',$this->orderId.'|'.$this->type->value.'|'.json_encode($this->data)); }
}
