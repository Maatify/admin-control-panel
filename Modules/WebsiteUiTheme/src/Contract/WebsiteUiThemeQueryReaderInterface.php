<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Contract;

use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDropdownCollectionDTO;

interface WebsiteUiThemeQueryReaderInterface
{
    public function listAllForDropdown(): WebsiteUiThemeDropdownCollectionDTO;

    public function listByEntityTypeForDropdown(string $entityType): WebsiteUiThemeDropdownCollectionDTO;
}
