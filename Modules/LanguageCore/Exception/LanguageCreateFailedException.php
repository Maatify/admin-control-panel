<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

use Maatify\LanguageCore\Domain\Enum\LanguageCoreErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class LanguageCreateFailedException extends LanguageCoreSystemException
{
    public function __construct()
    {
        parent::__construct('Failed to create language.');
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return LanguageCoreErrorCodeEnum::LANGUAGE_CREATE_FAILED;
    }
}
