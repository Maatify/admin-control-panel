<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

final class LanguageNotFoundException extends LanguageCoreException
{
    public function __construct(int|string $identifier)
    {
        parent::__construct(
            sprintf('Language not found (%s).', (string) $identifier)
        );
    }
}
