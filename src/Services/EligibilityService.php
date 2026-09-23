<?php

namespace YasserElgammal\Tamara\Services;

use Illuminate\Http\Client\ConnectionException;
use YasserElgammal\Tamara\DTOs\EligibilityResponse;
use YasserElgammal\Tamara\Exceptions\{ApiException, ValidationException};
use YasserElgammal\Tamara\Http\TamaraClient;

final class EligibilityService
{
    private const CURRENCIES = ['SAR', 'AED', 'BHD', 'KWD', 'OMR'];

    public function __construct(private readonly TamaraClient $client) {}

    public function check(float|int $amount, string $currency = 'SAR', ?string $phone = null): EligibilityResponse
    {
        $currency = strtoupper($currency);

        if ($amount < 0) {
            throw new ValidationException('Eligibility order amount cannot be negative.');
        }

        if (! in_array($currency, self::CURRENCIES, true)) {
            throw new ValidationException('Eligibility currency must be one of: '.implode(', ', self::CURRENCIES).'.');
        }

        $payload = [
            'order' => ['amount' => round((float) $amount, 3), 'currency' => $currency],
        ];

        if ($phone !== null && trim($phone) !== '') {
            $payload['customer'] = ['phone' => trim($phone)];
        }

        try {
            $data = $this->client->post('/pre-checkout/v1/eligibility', $payload, 0.2);
        } catch (ConnectionException) {
            // Tamara recommends showing the payment option if this non-critical
            // check times out or cannot obtain a response.
            return new EligibilityResponse(true, false);
        }

        if (! array_key_exists('is_eligible', $data) || ! is_bool($data['is_eligible'])) {
            throw new ApiException('Tamara returned an invalid eligibility response.', 502, $data);
        }

        return new EligibilityResponse($data['is_eligible'], true, $data);
    }
}
