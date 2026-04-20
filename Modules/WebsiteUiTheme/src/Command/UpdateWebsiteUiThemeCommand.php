<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Command;

final readonly class UpdateWebsiteUiThemeCommand
{
    public function __construct(
        public int $id,
        public string $entityType,
        public string $themeFile,
        public string $displayName,
    ) {}
}
