<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

use Maatify\LanguageCore\Domain\Enum\LanguageCoreErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class LanguageAlreadyExistsException extends LanguageCoreConflictException
{
    public function __construct(string $code)
    {
        parent::__construct(
            sprintf('Language with code "%s" already exists.', $code)
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return LanguageCoreErrorCodeEnum::LANGUAGE_ALREADY_EXISTS;
    }
}
