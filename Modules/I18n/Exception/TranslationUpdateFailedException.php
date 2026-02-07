<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class TranslationUpdateFailedException extends \RuntimeException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            "Translation update failed during operation: {$operation}"
        );
    }
}
