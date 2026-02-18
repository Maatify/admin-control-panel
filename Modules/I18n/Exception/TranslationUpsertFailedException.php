<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\I18n\Domain\Enum\I18nErrorCodeEnum;

final class TranslationUpsertFailedException extends I18nSystemException
{
    public function __construct(int $languageId, int $keyId)
    {
        parent::__construct(
            sprintf(
                'Failed to upsert translation (language_id=%d, key_id=%d).',
                $languageId,
                $keyId
            )
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return I18nErrorCodeEnum::TRANSLATION_UPSERT_FAILED;
    }
}
