<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Service;

use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDropdownCollectionDTO;

final readonly class WebsiteUiThemeFacade
{
    public function __construct(private WebsiteUiThemeQueryService $queryService) {}

    public function dropdown(): WebsiteUiThemeDropdownCollectionDTO
    {
        return $this->queryService->dropdown();
    }

    public function dropdownByEntityType(string $entityType): WebsiteUiThemeDropdownCollectionDTO
    {
        return $this->queryService->dropdownByEntityType($entityType);
    }
}
