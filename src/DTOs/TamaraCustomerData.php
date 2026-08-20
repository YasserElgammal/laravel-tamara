<?php

namespace YasserElgammal\Tamara\DTOs;

final readonly class TamaraCustomerData
{
    public function __construct(public string $firstName, public string $lastName, public string $email, public string $phone) {}

    /** @return array{first_name:string,last_name:string,email:string,phone_number:string} */
    public function toArray(): array { return ['first_name'=>$this->firstName,'last_name'=>$this->lastName,'email'=>$this->email,'phone_number'=>$this->phone]; }
}
