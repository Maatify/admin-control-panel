<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

final class LanguageUpdateFailedException extends LanguageCoreException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            sprintf('Failed to update language (%s).', $operation)
        );
    }
}
