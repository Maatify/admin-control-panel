<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

use Maatify\LanguageCore\Domain\Enum\LanguageCoreErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

final class LanguageNotFoundException extends LanguageCoreNotFoundException
{
    public function __construct(int|string $identifier)
    {
        parent::__construct(
            sprintf('Language not found (%s).', (string) $identifier)
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return LanguageCoreErrorCodeEnum::LANGUAGE_NOT_FOUND;
    }
}
