<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

use Maatify\LanguageCore\Domain\Enum\LanguageCoreErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class LanguageInvalidFallbackException extends LanguageCoreException
{
    public function __construct(int $languageId)
    {
        parent::__construct(
            sprintf('Language %d cannot be its own fallback.', $languageId)
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return LanguageCoreErrorCodeEnum::INVALID_LANGUAGE_FALLBACK;
    }
}
