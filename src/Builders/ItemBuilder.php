<?php

namespace YasserElgammal\Tamara\Builders;

use YasserElgammal\Tamara\DTOs\Money;
use YasserElgammal\Tamara\DTOs\TamaraItemData;
use YasserElgammal\Tamara\Exceptions\ValidationException;

final class ItemBuilder
{
    private ?string $name=null; private ?string $referenceId=null; private int $quantity=1; private ?float $unitPrice=null; private string $currency='SAR'; private string $type='Digital'; private ?string $sku=null;
    public function name(string $v): self { $this->name=trim($v); return $this; }
    public function referenceId(string $v): self { $this->referenceId=trim($v); return $this; }
    public function quantity(int $v): self { $this->quantity=$v; return $this; }
    public function unitPrice(float|int $v): self { $this->unitPrice=(float)$v; return $this; }
    public function currency(string $v): self { $this->currency=strtoupper($v); return $this; }
    public function type(string $v): self { $this->type=$v; return $this; }
    public function sku(string $v): self { $this->sku=$v; return $this; }
    public function build(): TamaraItemData
    {
        if (! $this->name || ! $this->referenceId || $this->unitPrice===null) throw new ValidationException('Item name, reference ID, and unit price are required.');
        if ($this->quantity < 1) throw new ValidationException('Item quantity must be at least one.');
        return new TamaraItemData($this->name,$this->referenceId,$this->quantity,new Money($this->unitPrice,$this->currency),$this->type,$this->sku);
    }
}
