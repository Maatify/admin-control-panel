<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class TranslationUpsertFailedException extends \RuntimeException
{
    public function __construct(
        int $languageId,
        int $keyId
    ) {
        parent::__construct(
            sprintf(
                'Failed to upsert translation (language_id=%d, key_id=%d).',
                $languageId,
                $keyId
            )
        );
    }
}
