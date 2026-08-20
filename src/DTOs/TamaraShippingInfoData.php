<?php

namespace YasserElgammal\Tamara\DTOs;

use DateTimeInterface;

final readonly class TamaraShippingInfoData
{
    public function __construct(
        public DateTimeInterface $shippedAt,
        public string $shippingCompany,
        public string $trackingNumber,
        public string $trackingUrl,
    ) {}

    /** @return array{shipped_at:string,shipping_company:string,tracking_number:string,tracking_url:string} */
    public function toArray(): array
    {
        return [
            'shipped_at' => $this->shippedAt->format('Y-m-d\TH:i:s.vP'),
            'shipping_company' => $this->shippingCompany,
            'tracking_number' => $this->trackingNumber,
            'tracking_url' => $this->trackingUrl,
        ];
    }
}
