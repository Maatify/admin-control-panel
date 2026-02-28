<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

use Maatify\LanguageCore\Domain\Enum\LanguageCoreErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class LanguageUpdateFailedException extends LanguageCoreSystemException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            sprintf('Failed to update language (%s).', $operation)
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return LanguageCoreErrorCodeEnum::LANGUAGE_UPDATE_FAILED;
    }
}
