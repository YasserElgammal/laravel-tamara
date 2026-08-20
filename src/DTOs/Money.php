<?php

namespace YasserElgammal\Tamara\DTOs;

use YasserElgammal\Tamara\Exceptions\ValidationException;

final readonly class Money
{
    public function __construct(public float $amount, public string $currency = 'SAR')
    {
        if ($amount < 0) throw new ValidationException('Amount cannot be negative.');
        if (! preg_match('/^[A-Z]{3}$/', strtoupper($currency))) throw new ValidationException('Currency must be a three-letter ISO code.');
    }

    /** @return array{amount: float, currency: string} */
    public function toArray(): array { return ['amount' => round($this->amount, 2), 'currency' => strtoupper($this->currency)]; }
}
