<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Command;

final readonly class DeleteWebsiteUiThemeCommand
{
    public function __construct(
        public int $id,
    ) {}
}
