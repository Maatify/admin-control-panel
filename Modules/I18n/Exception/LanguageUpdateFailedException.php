<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class LanguageUpdateFailedException extends I18nException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            sprintf('Failed to update language (%s).', $operation)
        );
    }
}
