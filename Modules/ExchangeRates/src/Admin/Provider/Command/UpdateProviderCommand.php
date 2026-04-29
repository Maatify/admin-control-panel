<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Command;

use Maatify\ExchangeRates\Exception\ExchangeRatesInvalidArgumentException;

/**
 * Self-validating command for updating a provider.
 *
 * Note: code is immutable after creation — not updatable via this command.
 */
final readonly class UpdateProviderCommand
{
    public function __construct(
        public int     $id,
        public string  $name,
        public ?string $description,
    ) {
        if ($id < 1) {
            throw ExchangeRatesInvalidArgumentException::invalidId('id');
        }
        if (trim($name) === '') {
            throw ExchangeRatesInvalidArgumentException::emptyField('name');
        }
        if (mb_strlen($name) > 100) {
            throw ExchangeRatesInvalidArgumentException::fieldTooLong('name', 100);
        }
    }
}
