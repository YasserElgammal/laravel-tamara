<?php

namespace YasserElgammal\Tamara\Builders;

use YasserElgammal\Tamara\DTOs\{Money,TamaraAddressData,TamaraCustomerData,TamaraItemData,TamaraOrderData};
use YasserElgammal\Tamara\Exceptions\ValidationException;

final class OrderBuilder
{
    private ?string $referenceId=null; private ?string $orderNumber=null; private ?float $total=null; private string $currency='SAR'; private ?TamaraCustomerData $customer=null; /** @var list<TamaraItemData> */ private array $items=[]; /** @var array{success:string,failure:string,cancel:string}|null */ private ?array $merchantUrls=null; private ?TamaraAddressData $shippingAddress=null; private string $countryCode='SA'; private string $description=''; private string $paymentType='PAY_BY_INSTALMENTS'; private string $locale='ar_SA'; private string $platform='Laravel';
    public function referenceId(string $v): self { $this->referenceId=trim($v); return $this; }
    public function orderNumber(string $v): self { $this->orderNumber=trim($v); return $this; }
    public function total(float|int $v): self { $this->total=(float)$v; return $this; }
    public function currency(string $v): self { $this->currency=strtoupper($v); return $this; }
    public function customer(TamaraCustomerData $v): self { $this->customer=$v; return $this; }
    public function item(TamaraItemData $v): self { $this->items[]=$v; return $this; }
    /** @param iterable<TamaraItemData> $items */
    public function items(iterable $items): self { $this->items=[]; foreach($items as $item) $this->item($item); return $this; }
    /** @template T @param iterable<T> $source @param callable(T):TamaraItemData $mapper */
    public function itemsFrom(iterable $source, callable $mapper): self { $this->items=[]; foreach($source as $value) $this->item($mapper($value)); return $this; }
    public function merchantUrls(string $success,string $failure,string $cancel): self { $this->merchantUrls=compact('success','failure','cancel'); return $this; }
    public function shippingAddress(TamaraAddressData $v): self { $this->shippingAddress=$v; return $this; }
    public function countryCode(string $v): self { $this->countryCode=strtoupper($v); return $this; }
    public function description(string $v): self { $this->description=$v; return $this; }
    public function paymentType(string $v): self { $this->paymentType=$v; return $this; }
    public function locale(string $v): self { $this->locale=$v; return $this; }
    public function platform(string $v): self { $this->platform=$v; return $this; }
    public function build(): TamaraOrderData
    {
        if (! $this->referenceId || $this->total===null || ! $this->customer || ! $this->items || ! $this->merchantUrls) throw new ValidationException('Order reference, total, customer, items, and merchant URLs are required.');
        $address=$this->shippingAddress ?? new TamaraAddressData($this->customer->firstName,$this->customer->lastName,'Riyadh','Riyadh',$this->countryCode,$this->customer->phone);
        return new TamaraOrderData($this->referenceId,$this->orderNumber ?: $this->referenceId,new Money($this->total,$this->currency),$this->customer,$this->items,$this->merchantUrls,$address,$this->countryCode,$this->description,$this->paymentType,$this->locale,$this->platform);
    }
}
