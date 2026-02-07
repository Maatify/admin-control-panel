<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class TranslationWriteFailedException extends I18nException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            sprintf('Translation write failed (%s).', $operation)
        );
    }
}
