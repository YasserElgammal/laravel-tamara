<?php

namespace YasserElgammal\Tamara\Services;

use YasserElgammal\Tamara\DTOs\{Money, TamaraItemData, TamaraShippingInfoData};
use YasserElgammal\Tamara\Http\TamaraClient;

final class PaymentService
{
    public function __construct(private readonly TamaraClient $client) {}
    /**
     * @param iterable<TamaraItemData> $items Items included in this full or partial capture.
     * @return array<string,mixed>
     */
    public function capture(
        string $orderId,
        float|int $amount,
        TamaraShippingInfoData $shippingInfo,
        string $currency = 'SAR',
        iterable $items = [],
    ): array {
        $payload = [
            'order_id' => $orderId,
            'total_amount' => (new Money((float) $amount, $currency))->toArray(),
            'shipping_info' => $shippingInfo->toArray(),
        ];

        $capturedItems = [];
        foreach ($items as $item) {
            $capturedItems[] = $item->toArray();
        }
        if ($capturedItems !== []) {
            $payload['items'] = $capturedItems;
        }

        return $this->client->post('/payments/capture', $payload);
    }
}
