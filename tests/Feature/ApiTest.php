<?php

namespace YasserElgammal\Tamara\Tests\Feature;

use Illuminate\Support\Facades\Http;
use DateTimeImmutable;
use YasserElgammal\Tamara\DTOs\TamaraShippingInfoData;
use YasserElgammal\Tamara\Exceptions\ApiException;
use YasserElgammal\Tamara\TamaraManager;
use YasserElgammal\Tamara\Tests\{CreatesOrders, TestCase};

final class ApiTest extends TestCase
{
    use CreatesOrders;
    public function test_creates_and_retrieves_order_without_real_http(): void
    {
        Http::fake(['*/checkout' => Http::response(['order_id' => 't-1', 'checkout_id' => 'c-1', 'checkout_url' => 'https://checkout.test', 'status' => 'new']), '*/orders/t-1' => Http::response(['order_id' => 't-1', 'status' => 'approved'])]);
        $orders = $this->app->make(TamaraManager::class)->orders();
        $result = $orders->create($this->validOrder());
        $this->assertSame('https://checkout.test', $result->url());
        $this->assertSame('c-1', $result->sdkPayload('pk')['checkout_id']);
        $this->assertSame('pk', $result->sdkPayload('pk')['public_key']);
        $this->assertSame('approved', $orders->get('t-1')['status']);
        Http::assertSentCount(2);
    }
    public function test_authorises_and_captures_order(): void
    {
        Http::fake(['*/orders/t-1/authorise' => Http::response(['status' => 'authorised']), '*/payments/capture' => Http::response(['status' => 'fully_captured'])]);
        $t = $this->app->make(TamaraManager::class);
        $shippingInfo = new TamaraShippingInfoData(
            new DateTimeImmutable('2026-08-17T10:30:00+00:00'),
            'DHL',
            'TRACK-100',
            'https://shipping.test/TRACK-100'
        );

        $item = $this->validOrder()->items[0];

        $this->assertSame('authorised', $t->orders()->authorise('t-1')['status']);
        $this->assertSame('fully_captured', $t->payments()->capture('t-1', 250, $shippingInfo, 'SAR', [$item])['status']);
        Http::assertSent(fn($r) => $r->url() === 'https://api-sandbox.tamara.co/payments/capture'
            && $r['total_amount']['amount'] === 250.0
            && $r['shipping_info']['shipping_company'] === 'DHL'
            && $r['shipping_info']['tracking_number'] === 'TRACK-100'
            && $r['items'][0]['reference_id'] === $item->referenceId);
    }
    public function test_exposes_api_and_authentication_failures(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Unauthenticated'], 401)]);
        $this->expectException(ApiException::class);
        $this->expectExceptionCode(401);
        $this->app->make(TamaraManager::class)->orders()->get('bad');
    }
    public function test_payment_types_query(): void
    {
        Http::fake(['*/checkout/payment-types*' => Http::response(['available_payment_types' => [['name' => 'PAY_BY_INSTALMENTS']]])]);
        $data = $this->app->make(TamaraManager::class)->orders()->paymentTypes('SA', 250);
        $this->assertCount(1, $data['available_payment_types']);
    }
}
