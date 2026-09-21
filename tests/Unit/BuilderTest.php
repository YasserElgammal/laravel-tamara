<?php

namespace YasserElgammal\Tamara\Tests\Unit;

use Illuminate\Support\Collection;
use YasserElgammal\Tamara\Exceptions\ValidationException;
use YasserElgammal\Tamara\TamaraManager;
use YasserElgammal\Tamara\Tests\{CreatesOrders, TestCase};

final class BuilderTest extends TestCase
{
    use CreatesOrders;
    public function test_builds_customer_item_and_order(): void
    {
        $order = $this->validOrder();
        $this->assertSame('ORDER-1001', $order->referenceId);
        $this->assertSame(250.0, $order->total->amount);
        $this->assertCount(1, $order->items);
        $this->assertSame(250.0, $order->toArray()['items'][0]['total_amount']['amount']);
    }

    public function test_accepts_multiple_items_and_collection(): void
    {
        $t = $this->app->make(TamaraManager::class);
        $customer = $t->customer()->firstName('A')->lastName('B')->email('a@example.com')->phone('5')->build();
        $items = new Collection([$t->item()->name('One')->referenceId('1')->unitPrice(40)->build(), $t->item()->name('Two')->referenceId('2')->quantity(2)->unitPrice(30)->build()]);
        $order = $t->order()->referenceId('R')->total(100)->customer($customer)->items($items)->merchantUrls('s', 'f', 'c')->build();
        $this->assertCount(2, $order->items);
    }

    public function test_items_from_maps_arbitrary_models(): void
    {
        $t = $this->app->make(TamaraManager::class);
        $customer = $t->customer()->firstName('A')->lastName('B')->email('a@example.com')->phone('5')->build();
        $rows = new Collection([(object)['id' => 1, 'name' => 'One', 'price' => 10], (object)['id' => 2, 'name' => 'Two', 'price' => 20]]);
        $order = $t->order()->referenceId('R')->total(30)->customer($customer)->itemsFrom($rows, fn($row) => $t->item()->name($row->name)->referenceId((string)$row->id)->unitPrice($row->price)->build())->merchantUrls('s', 'f', 'c')->build();
        $this->assertSame(['1', '2'], array_map(fn($i) => $i->referenceId, $order->items));
    }

    public function test_rejects_missing_required_data(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->make(TamaraManager::class)->order()->referenceId('R')->build();
    }

    public function test_rejects_invalid_customer_email(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->make(TamaraManager::class)->customer()->firstName('A')->lastName('B')->email('bad')->phone('5')->build();
    }
}
