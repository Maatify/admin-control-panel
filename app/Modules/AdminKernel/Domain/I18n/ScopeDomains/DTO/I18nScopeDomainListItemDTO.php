<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO;

use JsonSerializable;

/**
 * @phpstan-type I18nScopeDomainListItemArray array{
 *   code: string,
 *   name: string
 * }
 */
final readonly class I18nScopeDomainListItemDTO implements JsonSerializable
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}

    /**
     * @return I18nScopeDomainListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}
