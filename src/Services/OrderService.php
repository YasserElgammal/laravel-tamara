<?php

namespace YasserElgammal\Tamara\Services;

use YasserElgammal\Tamara\DTOs\{CheckoutResponse, Money, TamaraItemData, TamaraOrderData};
use YasserElgammal\Tamara\Exceptions\ApiException;
use YasserElgammal\Tamara\Http\TamaraClient;

final class OrderService
{
    public function __construct(private readonly TamaraClient $client) {}
    public function create(TamaraOrderData $order): CheckoutResponse
    {
        $data = $this->client->post('/checkout', $order->toArray());
        if (empty($data['order_id']) || empty($data['checkout_url'])) throw new ApiException('Tamara rejected the checkout session.', 422, $data);

        return new CheckoutResponse(
            (string)$data['order_id'],
            (string)($data['checkout_id'] ?? ''),
            (string)$data['checkout_url'],
            isset($data['status']) ? (string)$data['status'] : null,
            $data
        );
    }

    /** @return array<string,mixed> */ public function get(string $orderId): array
    {
        return $this->client->get('/orders/' . $orderId);
    }

    /** @return array<string,mixed> */ public function authorise(string $orderId): array
    {
        return $this->client->post('/orders/' . $orderId . '/authorise');
    }

    /**
     * Cancel an authorised order or update its amount before capture.
     *
     * @param iterable<TamaraItemData> $items The items remaining on the order.
     * @return array<string,mixed>
     */
    public function cancel(
        string $orderId,
        float|int $totalAmount,
        string $currency = 'SAR',
        float|int $shippingAmount = 0,
        float|int $taxAmount = 0,
        float|int $discountAmount = 0,
        iterable $items = [],
    ): array {
        $money = static fn(float|int $amount): array => (new Money((float) $amount, $currency))->toArray();
        $payload = [
            'total_amount' => $money($totalAmount),
            'shipping_amount' => $money($shippingAmount),
            'tax_amount' => $money($taxAmount),
            'discount_amount' => $money($discountAmount),
            'items' => [],
        ];

        foreach ($items as $item) {
            $data = $item->toArray();
            $data['discount_amount'] ??= $money(0);
            $data['tax_amount'] ??= $money(0);
            $payload['items'][] = $data;
        }

        return $this->client->post('/orders/' . $orderId . '/cancel', $payload);
    }

    /** @return array<string,mixed> */ public function paymentTypes(string $country, float $value, string $currency = 'SAR', ?string $phone = null): array
    {
        return $this->client->get('/checkout/payment-types', array_filter([
            'country' => $country,
            'currency' => $currency,
            'order_value' => $value,
            'phone' => $phone
        ], fn($v) => $v !== null && $v !== ''));
    }
}
