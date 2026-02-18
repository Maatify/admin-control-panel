<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\I18n\Domain\Enum\I18nErrorCodeEnum;

final class TranslationUpdateFailedException extends I18nSystemException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            "Translation update failed during operation: {$operation}"
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return I18nErrorCodeEnum::TRANSLATION_UPDATE_FAILED;
    }
}
