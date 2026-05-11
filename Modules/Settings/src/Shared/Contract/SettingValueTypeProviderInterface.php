<?php

declare(strict_types=1);

namespace Maatify\Settings\Shared\Contract;

interface SettingValueTypeProviderInterface
{
    /**
     * Get all available setting value types.
     *
     * @return list<string>
     */
    public function all(): array;

    /**
     * Check if a type is valid.
     */
    public function isValid(string $type): bool;

    /**
     * Get human-readable label for a type.
     */
    public function label(string $type): string;

    /**
     * Validate a value against its type.
     *
     * @throws \Maatify\Settings\Exception\SettingsInvalidArgumentException if invalid
     */
    public function validate(string $value, string $type): void;
}
