<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Contract;

use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;

/**
 * Read side — all queries on maa_image_profiles.
 */
interface ImageProfileQueryReaderInterface
{
    /**
     * @param   int                        $page
     * @param   int                        $perPage
     * @param   string|null                $globalSearch
     * @param   array<string, int|string>  $columnFilters
     *
     * @return ImageProfilePaginatedResultDTO
     */
    public function listProfiles(
        int $page,
        int $perPage,
        ?string $globalSearch,
        array $columnFilters,
    ): ImageProfilePaginatedResultDTO;

    /** @return list<ImageProfileDTO> */
    public function listActiveProfiles(): array;

    public function findById(int $id): ?ImageProfileDTO;

    public function findByCode(string $code): ?ImageProfileDTO;
}
