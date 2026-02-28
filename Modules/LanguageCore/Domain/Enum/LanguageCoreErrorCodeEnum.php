<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum LanguageCoreErrorCodeEnum: string implements ErrorCodeInterface
{
    // CONFLICT
    case LANGUAGE_ALREADY_EXISTS = 'LANGUAGE_ALREADY_EXISTS';

    // NOT_FOUND
    case LANGUAGE_NOT_FOUND = 'LANGUAGE_NOT_FOUND';

    // BUSINESS_RULE
    case INVALID_LANGUAGE_FALLBACK = 'INVALID_LANGUAGE_FALLBACK';

    // SYSTEM
    case LANGUAGE_CREATE_FAILED = 'LANGUAGE_CREATE_FAILED';
    case LANGUAGE_UPDATE_FAILED = 'LANGUAGE_UPDATE_FAILED';

    public function getValue(): string
    {
        return $this->value;
    }
}
