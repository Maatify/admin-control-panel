<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Service;

use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeCollectionDTO;

final readonly class WebsiteUiThemeFacade
{
    public function __construct(private WebsiteUiThemeQueryService $queryService) {}

    public function dropdown(): WebsiteUiThemeCollectionDTO
    {
        return $this->queryService->dropdown();
    }

    public function dropdownByEntityType(string $entityType): WebsiteUiThemeCollectionDTO
    {
        return $this->queryService->dropdownByEntityType($entityType);
    }

    public function existsByThemeFile(string $themeFile): bool
    {
        return $this->queryService->existsByThemeFile($themeFile);
    }

    public function existsByThemeFileAndEntityType(string $themeFile, string $entityType): bool
    {
        return $this->queryService->existsByThemeFileAndEntityType($themeFile, $entityType);
    }
}
