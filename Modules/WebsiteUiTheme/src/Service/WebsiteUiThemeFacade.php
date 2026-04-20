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
}
