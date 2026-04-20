<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\DTO;

use JsonSerializable;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemeInvalidArgumentException;

final readonly class WebsiteUiThemeDropdownItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $entityType,
        public string $themeFile,
        public string $displayName,
    ) {}

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: self::int($row['id'] ?? null),
            entityType: self::string($row['entity_type'] ?? null),
            themeFile: self::string($row['theme_file'] ?? null),
            displayName: self::string($row['display_name'] ?? null),
        );
    }

    /** @return array{id:int,entity_type:string,theme_file:string,display_name:string} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entityType,
            'theme_file' => $this->themeFile,
            'display_name' => $this->displayName,
        ];
    }

    private static function int(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        throw WebsiteUiThemeInvalidArgumentException::unexpectedType('int field', $value);
    }

    private static function string(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        throw WebsiteUiThemeInvalidArgumentException::unexpectedType('string field', $value);
    }
}
