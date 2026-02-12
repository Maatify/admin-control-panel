<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

final class LanguageAlreadyExistsException extends LanguageCoreException
{
    public function __construct(string $code)
    {
        parent::__construct(
            sprintf('Language with code "%s" already exists.', $code)
        );
    }
}
