<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

final class WebsiteUiThemeAlreadyExistsException extends GenericConflictMaatifyException
    implements WebsiteUiThemeExceptionInterface
{
    public static function withEntityTypeAndThemeFile(string $entityType, string $themeFile): self
    {
        return new self(sprintf(
            'A website UI theme with entity_type "%s" and theme_file "%s" already exists.',
            $entityType,
            $themeFile,
        ));
    }
}
