<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Exception;

final class LanguageCreateFailedException extends LanguageCoreException
{
    public function __construct()
    {
        parent::__construct('Failed to create language.');
    }
}
