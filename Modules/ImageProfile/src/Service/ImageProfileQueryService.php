<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Service;

use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;

final class ImageProfileQueryService
{
    public function __construct(private readonly ImageProfileQueryReaderInterface $reader) {}

    /** @param array<string, int|string> $columnFilters */
    public function paginate(
        int $page = 1,
        int $perPage = 20,
        ?string $globalSearch = null,
        array $columnFilters = [],
    ): ImageProfilePaginatedResultDTO {
        return $this->reader->listProfiles($page, $perPage, $globalSearch, $columnFilters);
    }

    public function activeList(): ImageProfileCollectionDTO
    {
        return new ImageProfileCollectionDTO($this->reader->listActiveProfiles());
    }

    public function getById(int $id): ImageProfileDTO
    {
        $dto = $this->reader->findById($id);
        if ($dto === null) {
            throw ImageProfileNotFoundException::withId($id);
        }

        return $dto;
    }

    public function getByCode(string $code): ImageProfileDTO
    {
        $dto = $this->reader->findByCode($code);
        if ($dto === null) {
            throw ImageProfileNotFoundException::withCode($code);
        }

        return $dto;
    }
}
