<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Service;

use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeQueryReaderInterface;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeCollectionDTO;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDTO;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemePaginatedResultDTO;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemeNotFoundException;

final readonly class WebsiteUiThemeQueryService
{
    public function __construct(private WebsiteUiThemeQueryReaderInterface $reader) {}

    /** @param array<string, int|string> $columnFilters */
    public function paginate(
        int $page = 1,
        int $perPage = 20,
        ?string $globalSearch = null,
        array $columnFilters = [],
    ): WebsiteUiThemePaginatedResultDTO {
        return $this->reader->listThemes($page, $perPage, $globalSearch, $columnFilters);
    }

    public function listAll(): WebsiteUiThemeCollectionDTO
    {
        return new WebsiteUiThemeCollectionDTO($this->reader->listAllThemes());
    }

    public function listByEntityType(string $entityType): WebsiteUiThemeCollectionDTO
    {
        return new WebsiteUiThemeCollectionDTO($this->reader->listThemesByEntityType($entityType));
    }

    public function getById(int $id): WebsiteUiThemeDTO
    {
        $dto = $this->reader->findById($id);
        if ($dto === null) {
            throw WebsiteUiThemeNotFoundException::withId($id);
        }

        return $dto;
    }

    public function getByEntityTypeAndThemeFile(string $entityType, string $themeFile): WebsiteUiThemeDTO
    {
        $dto = $this->reader->findByEntityTypeAndThemeFile($entityType, $themeFile);
        if ($dto === null) {
            throw WebsiteUiThemeNotFoundException::withEntityTypeAndThemeFile($entityType, $themeFile);
        }

        return $dto;
    }

    public function dropdown(): WebsiteUiThemeCollectionDTO
    {
        return $this->listAll();
    }

    public function dropdownByEntityType(string $entityType): WebsiteUiThemeCollectionDTO
    {
        return $this->listByEntityType($entityType);
    }
}
