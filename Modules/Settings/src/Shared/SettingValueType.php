<?php

declare(strict_types=1);

namespace Maatify\Settings\Shared;

enum SettingValueType: string
{
    case BOOL = 'bool';
    case INT = 'int';
    case STRING = 'string';
    case DATE = 'date';
    case DATETIME = 'datetime';

    public function label(): string
    {
        return match ($this) {
            self::BOOL => 'Boolean',
            self::INT => 'Integer',
            self::STRING => 'String',
            self::DATE => 'Date (YYYY-MM-DD)',
            self::DATETIME => 'DateTime (YYYY-MM-DD HH:MM:SS)',
        };
    }

    public static function fromValue(string $value): self
    {
        return self::tryFrom($value) ?? throw new \ValueError(
            "Invalid setting value type: {$value}. Allowed: " . implode(', ', array_map(fn(self $e) => $e->value, self::cases()))
        );
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
