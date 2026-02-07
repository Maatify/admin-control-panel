<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class LanguageNotFoundException extends I18nException
{
    public function __construct(int|string $identifier)
    {
        parent::__construct(
            sprintf('Language not found (%s).', (string) $identifier)
        );
    }
}
