<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\I18n\Domain\Enum\I18nErrorCodeEnum;

final class TranslationKeyAlreadyExistsException extends I18nConflictException
{
    public function __construct(string $scope, string $domain, string $key)
    {
        parent::__construct(
            sprintf(
                'Translation key already exists: %s.%s.%s',
                $scope,
                $domain,
                $key
            )
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return I18nErrorCodeEnum::TRANSLATION_KEY_ALREADY_EXISTS;
    }
}
