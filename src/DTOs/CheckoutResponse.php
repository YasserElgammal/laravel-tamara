<?php

namespace YasserElgammal\Tamara\DTOs;

final readonly class CheckoutResponse
{
    /** @param array<string,mixed> $raw */
    public function __construct(public string $orderId, public string $checkoutId, public string $checkoutUrl, public ?string $status, public array $raw) {}
    public function url(): string { return $this->checkoutUrl; }
    /** @return array<string,mixed> */
    public function sdkPayload(?string $publicKey): array { return ['order_id'=>$this->orderId,'checkout_id'=>$this->checkoutId,'checkout_url'=>$this->checkoutUrl,'status'=>$this->status,'public_key'=>$publicKey]; }
}
