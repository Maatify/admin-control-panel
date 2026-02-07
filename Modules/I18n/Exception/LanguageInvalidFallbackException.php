<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class LanguageInvalidFallbackException extends I18nException
{
    public function __construct(int $languageId)
    {
        parent::__construct(
            sprintf('Language %d cannot be its own fallback.', $languageId)
        );
    }
}
