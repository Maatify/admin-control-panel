<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\AppSettingsList;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type AppSettingsListResponseArray array{
 *   data: AppSettingsListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final readonly class AppSettingsListResponseDTO implements JsonSerializable
{
    /**
     * @param AppSettingsListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {}

    /**
     * @return AppSettingsListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
