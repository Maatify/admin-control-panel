<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Command;

use Maatify\ExchangeRates\Exception\ExchangeRatesInvalidArgumentException;

/**
 * Self-validating command for creating a new provider.
 *
 * Rules (MODULE_BUILDING_STANDARD §7):
 *   - final readonly
 *   - All validation in constructor only
 *   - No business logic
 *   - code is uppercased here — stored uppercase, immutable after creation
 */
final readonly class CreateProviderCommand
{
    public string $code;

    public function __construct(
        public string  $name,
        string         $code,
        public ?string $description,
    ) {
        if (trim($name) === '') {
            throw ExchangeRatesInvalidArgumentException::emptyField('name');
        }
        if (mb_strlen($name) > 100) {
            throw ExchangeRatesInvalidArgumentException::fieldTooLong('name', 100);
        }

        $code = strtoupper(trim($code));
        if ($code === '') {
            throw ExchangeRatesInvalidArgumentException::emptyField('code');
        }
        if (mb_strlen($code) > 50) {
            throw ExchangeRatesInvalidArgumentException::fieldTooLong('code', 50);
        }
        $this->code = $code;
    }
}
