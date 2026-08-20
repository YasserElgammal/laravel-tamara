<?php

namespace YasserElgammal\Tamara\Tests;

use YasserElgammal\Tamara\DTOs\TamaraOrderData;
use YasserElgammal\Tamara\TamaraManager;

trait CreatesOrders
{
    protected function validOrder(): TamaraOrderData
    {
        $tamara=$this->app->make(TamaraManager::class);
        $customer=$tamara->customer()->firstName('Yasser')->lastName('Elgammal')->email('test@example.com')->phone('501234567')->build();
        $item=$tamara->item()->name('Laravel Course')->referenceId('10')->quantity(1)->unitPrice(250)->build();
        return $tamara->order()->referenceId('ORDER-1001')->currency('SAR')->total(250)->customer($customer)->item($item)->merchantUrls('https://app.test/success','https://app.test/failure','https://app.test/cancel')->build();
    }
}
