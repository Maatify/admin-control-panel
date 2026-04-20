<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

final class WebsiteUiThemeNotFoundException extends ResourceNotFoundMaatifyException
    implements WebsiteUiThemeExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Website UI theme with id %d not found.', $id));
    }

    public static function withEntityTypeAndThemeFile(string $entityType, string $themeFile): self
    {
        return new self(sprintf(
            'Website UI theme with entity_type "%s" and theme_file "%s" not found.',
            $entityType,
            $themeFile,
        ));
    }
}
