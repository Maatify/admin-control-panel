<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class TranslationKeyAlreadyExistsException extends I18nException
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
}
