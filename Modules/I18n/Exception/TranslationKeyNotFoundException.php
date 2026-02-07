<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class TranslationKeyNotFoundException extends \RuntimeException
{
    public function __construct(int $keyId)
    {
        parent::__construct(
            sprintf(
                'Translation key not found (id: %d).',
                $keyId
            )
        );
    }
}
