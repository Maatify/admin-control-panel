<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Command;

final readonly class CreateWebsiteUiThemeCommand
{
    public function __construct(
        public string $entityType,
        public string $themeFile,
        public string $displayName,
    ) {}
}
