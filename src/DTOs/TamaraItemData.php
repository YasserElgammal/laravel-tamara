<?php

namespace YasserElgammal\Tamara\DTOs;

final readonly class TamaraItemData
{
    public function __construct(public string $name, public string $referenceId, public int $quantity, public Money $unitPrice, public string $type = 'Digital', public ?string $sku = null) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['name'=>$this->name,'type'=>$this->type,'reference_id'=>$this->referenceId,'sku'=>$this->sku ?? $this->referenceId,'quantity'=>$this->quantity,'unit_price'=>$this->unitPrice->toArray(),'total_amount'=>(new Money($this->unitPrice->amount * $this->quantity, $this->unitPrice->currency))->toArray()];
    }
}
