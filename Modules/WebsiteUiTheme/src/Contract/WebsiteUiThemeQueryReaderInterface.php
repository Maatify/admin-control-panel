<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Contract;

use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDTO;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemePaginatedResultDTO;

interface WebsiteUiThemeQueryReaderInterface
{
    /**
     * @param array<string, int|string> $columnFilters
     */
    public function listThemes(
        int $page,
        int $perPage,
        ?string $globalSearch,
        array $columnFilters,
    ): WebsiteUiThemePaginatedResultDTO;

    /** @return list<WebsiteUiThemeDTO> */
    public function listAllThemes(): array;

    /** @return list<WebsiteUiThemeDTO> */
    public function listThemesByEntityType(string $entityType): array;

    public function findById(int $id): ?WebsiteUiThemeDTO;

    public function findByEntityTypeAndThemeFile(string $entityType, string $themeFile): ?WebsiteUiThemeDTO;

    public function existsByThemeFile(string $themeFile): bool;

    public function existsByThemeFileAndEntityType(string $themeFile, string $entityType): bool;
}
