<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class LanguageAlreadyExistsException extends I18nException
{
    public function __construct(string $code)
    {
        parent::__construct(
            sprintf('Language with code "%s" already exists.', $code)
        );
    }
}
