<?php

namespace YasserElgammal\Tamara\Tests\Feature;

use Illuminate\Support\Facades\Http;
use DateTimeImmutable;
use YasserElgammal\Tamara\DTOs\TamaraShippingInfoData;
use YasserElgammal\Tamara\Exceptions\ApiException;
use YasserElgammal\Tamara\Exceptions\ValidationException;
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

    public function test_cancels_or_updates_an_authorised_order(): void
    {
        Http::fake(['*/orders/t-1/cancel' => Http::response(['status' => 'updated'])]);
        $orders = $this->app->make(TamaraManager::class)->orders();
        $item = $this->validOrder()->items[0];

        $result = $orders->cancel('t-1', 300, 'SAR', 0, 100, 10, [$item]);

        $this->assertSame('updated', $result['status']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.tamara.co/orders/t-1/cancel'
            && $request['total_amount'] === ['amount' => 300.0, 'currency' => 'SAR']
            && $request['shipping_amount'] === ['amount' => 0.0, 'currency' => 'SAR']
            && $request['tax_amount'] === ['amount' => 100.0, 'currency' => 'SAR']
            && $request['discount_amount'] === ['amount' => 10.0, 'currency' => 'SAR']
            && $request['items'][0]['reference_id'] === $item->referenceId
            && $request['items'][0]['discount_amount'] === ['amount' => 0.0, 'currency' => 'SAR']
            && $request['items'][0]['tax_amount'] === ['amount' => 0.0, 'currency' => 'SAR']);
    }

    public function test_fully_refunds_a_captured_order(): void
    {
        Http::fake(['*/payments/simplified-refund/t-1' => Http::response([
            'order_id' => 't-1',
            'refund_id' => 'refund-1',
            'capture_id' => 'capture-1',
            'status' => 'fully_refunded',
            'refunded_amount' => ['amount' => 300, 'currency' => 'SAR'],
        ])]);

        $result = $this->app->make(TamaraManager::class)->payments()->refund(
            't-1',
            300,
            'Refund for order A123',
            'SAR',
            'merchant-refund-1',
        );

        $this->assertSame('fully_refunded', $result['status']);
        $this->assertSame('refund-1', $result['refund_id']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.tamara.co/payments/simplified-refund/t-1'
            && $request['total_amount'] === ['amount' => 300.0, 'currency' => 'SAR']
            && $request['comment'] === 'Refund for order A123'
            && $request['merchant_refund_id'] === 'merchant-refund-1');
    }

    public function test_partially_refunds_a_captured_order_without_a_merchant_refund_id(): void
    {
        Http::fake(['*/payments/simplified-refund/t-1' => Http::response([
            'order_id' => 't-1',
            'refund_id' => 'refund-2',
            'capture_id' => 'capture-1',
            'status' => 'partially_refunded',
            'refunded_amount' => ['amount' => 100, 'currency' => 'SAR'],
        ])]);

        $result = $this->app->make(TamaraManager::class)->payments()->refund(
            orderId: 't-1',
            amount: 100,
            comment: 'Partial refund for order A123',
        );

        $this->assertSame('partially_refunded', $result['status']);
        $this->assertSame(100, $result['refunded_amount']['amount']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.tamara.co/payments/simplified-refund/t-1'
            && $request['total_amount'] === ['amount' => 100.0, 'currency' => 'SAR']
            && $request['comment'] === 'Partial refund for order A123'
            && ! isset($request['merchant_refund_id']));
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

    public function test_checks_precheckout_eligibility_with_a_200ms_timeout(): void
    {
        Http::fake(['*/pre-checkout/v1/eligibility' => Http::response(['is_eligible' => false])]);

        $result = $this->app->make(TamaraManager::class)->eligibility()->check(250, 'SAR', '966504591298');

        $this->assertFalse($result->eligible());
        $this->assertTrue($result->wasChecked);
        Http::assertSent(fn ($request) => $request['order'] === ['amount' => 250.0, 'currency' => 'SAR']
            && $request['customer'] === ['phone' => '966504591298']);
    }

    public function test_eligibility_omits_an_empty_phone_and_fails_open_without_a_response(): void
    {
        Http::fake(['*' => Http::failedConnection('Timed out')]);

        $result = $this->app->make(TamaraManager::class)->eligibility()->check(100, 'AED');

        $this->assertTrue($result->eligible());
        $this->assertFalse($result->wasChecked);
        Http::assertSent(fn ($request) => ! isset($request['customer']));
    }

    public function test_eligibility_rejects_an_unsupported_currency(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->make(TamaraManager::class)->eligibility()->check(100, 'USD');
    }
}
