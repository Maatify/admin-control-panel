<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\ScopeDomains\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type I18nScopeDomainsListResponseArray array{
 *   data: I18nScopeDomainsListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class I18nScopeDomainsListResponseDTO implements JsonSerializable
{
    /**
     * @param I18nScopeDomainsListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    /**
     * @return I18nScopeDomainsListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
