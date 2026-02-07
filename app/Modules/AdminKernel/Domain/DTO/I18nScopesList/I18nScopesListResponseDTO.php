<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\I18nScopesList;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type I18nScopesListResponseArray array{
 *   data: I18nScopesListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class I18nScopesListResponseDTO implements JsonSerializable
{
    /**
     * @param I18nScopesListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    /**
     * @return I18nScopesListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
