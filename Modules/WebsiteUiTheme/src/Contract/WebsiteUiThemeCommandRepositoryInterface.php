<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Contract;

use Maatify\WebsiteUiTheme\Command\CreateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Command\UpdateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDTO;

interface WebsiteUiThemeCommandRepositoryInterface
{
    public function create(CreateWebsiteUiThemeCommand $command): WebsiteUiThemeDTO;

    public function update(UpdateWebsiteUiThemeCommand $command): WebsiteUiThemeDTO;
}
