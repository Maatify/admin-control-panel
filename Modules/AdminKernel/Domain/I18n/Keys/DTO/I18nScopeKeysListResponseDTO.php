<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\DTO;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type I18nScopeKeysListResponseArray array{
 *   data: I18nScopeKeyListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class I18nScopeKeysListResponseDTO implements \JsonSerializable
{
    /**
     * @param I18nScopeKeyListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    /**
     * @return I18nScopeKeysListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data'       => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}

