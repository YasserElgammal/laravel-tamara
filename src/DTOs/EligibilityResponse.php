<?php

namespace YasserElgammal\Tamara\DTOs;

final readonly class EligibilityResponse
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public bool $isEligible,
        public bool $wasChecked = true,
        public array $raw = [],
    ) {}

    public function eligible(): bool
    {
        return $this->isEligible;
    }
}
