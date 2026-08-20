<?php

namespace YasserElgammal\Tamara\Facades;

use Illuminate\Support\Facades\Facade;
use YasserElgammal\Tamara\TamaraManager;

/** @method static \YasserElgammal\Tamara\Builders\OrderBuilder order() @method static \YasserElgammal\Tamara\Builders\ItemBuilder item() @method static \YasserElgammal\Tamara\Builders\CustomerBuilder customer() @method static \YasserElgammal\Tamara\Services\OrderService orders() @method static \YasserElgammal\Tamara\Services\PaymentService payments() */
final class Tamara extends Facade { protected static function getFacadeAccessor(): string{return TamaraManager::class;} }
