<?php

namespace YasserElgammal\Tamara;

use YasserElgammal\Tamara\Builders\{CustomerBuilder, ItemBuilder, OrderBuilder};
use YasserElgammal\Tamara\Services\{OrderService, PaymentService};

final class TamaraManager
{
    public function __construct(private readonly OrderService $orders, private readonly PaymentService $payments) {}
    public function order(): OrderBuilder
    {
        return new OrderBuilder();
    }

    public function item(): ItemBuilder
    {
        return new ItemBuilder();
    }

    public function customer(): CustomerBuilder
    {
        return new CustomerBuilder();
    }

    public function orders(): OrderService
    {
        return $this->orders;
    }

    public function payments(): PaymentService
    {
        return $this->payments;
    }

    public function isConfigured(): bool
    {
        return (bool)config('tamara.enabled') && (string)config('tamara.api_token') !== '';
    }
}
