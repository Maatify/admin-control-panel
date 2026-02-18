<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum AppSettingsErrorCodeEnum: string implements ErrorCodeInterface
{
    // NOT_FOUND
    case APP_SETTING_NOT_FOUND = 'APP_SETTING_NOT_FOUND';

    // CONFLICT
    case DUPLICATE_APP_SETTING = 'DUPLICATE_APP_SETTING';

    // BUSINESS_RULE
    case APP_SETTING_PROTECTED = 'APP_SETTING_PROTECTED';

    // VALIDATION
    case INVALID_APP_SETTING = 'INVALID_APP_SETTING';

    public function getValue(): string
    {
        return $this->value;
    }
}
