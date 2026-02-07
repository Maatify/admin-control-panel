<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class LanguageCreateFailedException extends I18nException
{
    public function __construct()
    {
        parent::__construct('Failed to create language.');
    }
}
