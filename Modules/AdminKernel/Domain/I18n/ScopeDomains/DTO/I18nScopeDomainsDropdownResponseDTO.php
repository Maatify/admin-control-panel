<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO;

/**
 * @phpstan-type I18nScopeDomainsDropdownResponseArray array{
 *   data: I18nScopeDomainListItemDTO[]
 * }
 */
final readonly class I18nScopeDomainsDropdownResponseDTO implements \JsonSerializable
{
    /**
     * @param I18nScopeDomainListItemDTO[] $data
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * @return I18nScopeDomainsDropdownResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
