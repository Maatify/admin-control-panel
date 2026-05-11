<?php

declare(strict_types=1);

namespace Maatify\Settings\Shared\Infrastructure;

use Maatify\Settings\Exception\SettingsInvalidArgumentException;
use Maatify\Settings\Shared\Contract\SettingValueTypeProviderInterface;
use Maatify\Settings\Shared\SettingValueType;

final class DefaultSettingValueTypeProvider implements SettingValueTypeProviderInterface
{
    /** @return list<string> */
    public function all(): array
    {
        return SettingValueType::values();
    }

    public function isValid(string $type): bool
    {
        return SettingValueType::tryFrom($type) !== null;
    }

    public function label(string $type): string
    {
        try {
            return SettingValueType::fromValue($type)->label();
        } catch (\ValueError) {
            throw SettingsInvalidArgumentException::invalidValueType($type);
        }
    }

    public function validate(string $value, string $type): void
    {
        try {
            $enumType = SettingValueType::fromValue($type);
        } catch (\ValueError) {
            throw SettingsInvalidArgumentException::invalidValueType($type);
        }

        match ($enumType) {
            SettingValueType::BOOL => $this->validateBool($value, $enumType),
            SettingValueType::INT => $this->validateInt($value, $enumType),
            SettingValueType::DATETIME => $this->validateDateTime($value, $enumType),
            SettingValueType::DATE => $this->validateDate($value, $enumType),
            SettingValueType::STRING => true,
        };
    }

    private function validateBool(string $value, SettingValueType $type): void
    {
        if ($value !== '0' && $value !== '1') {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $type->label());
        }
    }

    private function validateInt(string $value, SettingValueType $type): void
    {
        if (! preg_match('/^-?\d+$/', $value)) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $type->label());
        }
    }

    private function validateDateTime(string $value, SettingValueType $type): void
    {
        try {
            new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $type->label());
        }
    }

    private function validateDate(string $value, SettingValueType $type): void
    {
        try {
            new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $type->label());
        }
    }
}
