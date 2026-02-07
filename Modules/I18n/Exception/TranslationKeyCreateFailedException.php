<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class TranslationKeyCreateFailedException extends \RuntimeException
{
    public function __construct(
        string $scope,
        string $domain,
        string $key
    ) {
        parent::__construct(
            sprintf(
                'Failed to create translation key [%s.%s.%s].',
                $scope,
                $domain,
                $key
            )
        );
    }
}
