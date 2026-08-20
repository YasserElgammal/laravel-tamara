<?php

namespace YasserElgammal\Tamara\Exceptions;

final class ApiException extends TamaraException
{
    /** @param array<string, mixed> $response */
    public function __construct(string $message, public readonly int $status, public readonly array $response = [])
    {
        parent::__construct($message, $status);
    }
}
