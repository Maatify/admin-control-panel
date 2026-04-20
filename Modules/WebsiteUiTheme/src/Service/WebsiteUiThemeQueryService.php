<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Service;

use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeQueryReaderInterface;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDropdownCollectionDTO;

final readonly class WebsiteUiThemeQueryService
{
    public function __construct(private WebsiteUiThemeQueryReaderInterface $reader) {}

    public function dropdown(): WebsiteUiThemeDropdownCollectionDTO
    {
        return $this->reader->listAllForDropdown();
    }

    public function dropdownByEntityType(string $entityType): WebsiteUiThemeDropdownCollectionDTO
    {
        return $this->reader->listByEntityTypeForDropdown($entityType);
    }
}
