<?php

declare(strict_types=1);

namespace Maatify\Settings\Exception;

final class SettingsNotFoundException extends \RuntimeException implements SettingsExceptionInterface
{
    public static function withKey(string $key): self
    {
        return new self("Setting with key [{$key}] not found.");
    }
}
