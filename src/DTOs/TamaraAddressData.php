<?php

namespace YasserElgammal\Tamara\DTOs;

final readonly class TamaraAddressData
{
    public function __construct(public string $firstName, public string $lastName, public string $line1, public string $city, public string $countryCode, public string $phone) {}
    /** @return array<string,string> */
    public function toArray(): array { return ['first_name'=>$this->firstName,'last_name'=>$this->lastName,'line1'=>$this->line1,'city'=>$this->city,'country_code'=>$this->countryCode,'phone_number'=>$this->phone]; }
}
