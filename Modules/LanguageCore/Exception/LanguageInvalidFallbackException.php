<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

final class LanguageInvalidFallbackException extends LanguageCoreException
{
    public function __construct(int $languageId)
    {
        parent::__construct(
            sprintf('Language %d cannot be its own fallback.', $languageId)
        );
    }
}
