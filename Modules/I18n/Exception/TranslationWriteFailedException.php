<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\I18n\Domain\Enum\I18nErrorCodeEnum;

final class TranslationWriteFailedException extends I18nSystemException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            sprintf('Translation write failed (%s).', $operation)
        );
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return I18nErrorCodeEnum::TRANSLATION_WRITE_FAILED;
    }
}
