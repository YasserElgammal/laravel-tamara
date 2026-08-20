<?php

namespace YasserElgammal\Tamara\Builders;

use YasserElgammal\Tamara\DTOs\TamaraCustomerData;
use YasserElgammal\Tamara\Exceptions\ValidationException;

final class CustomerBuilder
{
    private ?string $firstName=null; private ?string $lastName=null; private ?string $email=null; private ?string $phone=null;
    public function firstName(string $value): self { $this->firstName=trim($value); return $this; }
    public function lastName(string $value): self { $this->lastName=trim($value); return $this; }
    public function email(string $value): self { $this->email=trim($value); return $this; }
    public function phone(string $value): self { $this->phone=trim($value); return $this; }
    public function build(): TamaraCustomerData
    {
        if (! $this->firstName || ! $this->lastName || ! $this->email || ! $this->phone) throw new ValidationException('Customer first name, last name, email, and phone are required.');
        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) throw new ValidationException('Customer email is invalid.');
        return new TamaraCustomerData($this->firstName,$this->lastName,$this->email,$this->phone);
    }
}
