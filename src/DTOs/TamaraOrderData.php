<?php

namespace YasserElgammal\Tamara\DTOs;

final readonly class TamaraOrderData
{
    /** @param list<TamaraItemData> $items @param array{success:string,failure:string,cancel:string} $merchantUrls */
    public function __construct(public string $referenceId, public string $orderNumber, public Money $total, public TamaraCustomerData $customer, public array $items, public array $merchantUrls, public TamaraAddressData $shippingAddress, public string $countryCode='SA', public string $description='', public string $paymentType='PAY_BY_INSTALMENTS', public string $locale='ar_SA', public string $platform='Laravel') {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $zero=(new Money(0,$this->total->currency))->toArray();
        return ['order_reference_id'=>$this->referenceId,'order_number'=>$this->orderNumber,'total_amount'=>$this->total->toArray(),'shipping_amount'=>$zero,'tax_amount'=>$zero,'discount'=>['name'=>'-','amount'=>$zero],'items'=>array_map(fn(TamaraItemData $i)=>$i->toArray(),$this->items),'consumer'=>$this->customer->toArray(),'country_code'=>$this->countryCode,'description'=>$this->description ?: 'Order '.$this->referenceId,'payment_type'=>$this->paymentType,'locale'=>$this->locale,'merchant_url'=>$this->merchantUrls,'shipping_address'=>$this->shippingAddress->toArray(),'platform'=>$this->platform];
    }
}
