<?php

declare(strict_types=1);

namespace Maatify\I18n\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum I18nErrorCodeEnum: string implements ErrorCodeInterface
{
    // BUSINESS RULE
    case DOMAIN_NOT_ALLOWED = 'DOMAIN_NOT_ALLOWED';
    case DOMAIN_SCOPE_VIOLATION = 'DOMAIN_SCOPE_VIOLATION';
    case SCOPE_NOT_ALLOWED = 'SCOPE_NOT_ALLOWED';

    // CONFLICT
    case TRANSLATION_KEY_ALREADY_EXISTS = 'TRANSLATION_KEY_ALREADY_EXISTS';

    // NOT FOUND
    case TRANSLATION_KEY_NOT_FOUND = 'TRANSLATION_KEY_NOT_FOUND';

    // SYSTEM
    case TRANSLATION_KEY_CREATE_FAILED = 'TRANSLATION_KEY_CREATE_FAILED';
    case TRANSLATION_UPDATE_FAILED = 'TRANSLATION_UPDATE_FAILED';
    case TRANSLATION_UPSERT_FAILED = 'TRANSLATION_UPSERT_FAILED';
    case TRANSLATION_WRITE_FAILED = 'TRANSLATION_WRITE_FAILED';

    public function getValue(): string
    {
        return $this->value;
    }
}
