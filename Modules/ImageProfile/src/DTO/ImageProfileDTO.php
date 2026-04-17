<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use JsonSerializable;
use Maatify\ImageProfile\Exception\ImageProfileInvalidArgumentException;

final class ImageProfileDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly ?string $displayName,
        public readonly ?int $minWidth,
        public readonly ?int $minHeight,
        public readonly ?int $maxWidth,
        public readonly ?int $maxHeight,
        public readonly ?int $maxSizeBytes,
        public readonly ?string $allowedExtensions,
        public readonly ?string $allowedMimeTypes,
        public readonly bool $isActive,
        public readonly ?string $notes,
        public readonly ?string $minAspectRatio,
        public readonly ?string $maxAspectRatio,
        public readonly bool $requiresTransparency,
        public readonly ?string $preferredFormat,
        public readonly ?int $preferredQuality,
        public readonly ?string $variants,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: self::int($row['id']),
            code: self::string($row['code']),
            displayName: self::nullableString($row['display_name'] ?? null),
            minWidth: self::nullableInt($row['min_width'] ?? null),
            minHeight: self::nullableInt($row['min_height'] ?? null),
            maxWidth: self::nullableInt($row['max_width'] ?? null),
            maxHeight: self::nullableInt($row['max_height'] ?? null),
            maxSizeBytes: self::nullableInt($row['max_size_bytes'] ?? null),
            allowedExtensions: self::nullableString($row['allowed_extensions'] ?? null),
            allowedMimeTypes: self::nullableString($row['allowed_mime_types'] ?? null),
            isActive: self::bool($row['is_active']),
            notes: self::nullableString($row['notes'] ?? null),
            minAspectRatio: self::nullableString($row['min_aspect_ratio'] ?? null),
            maxAspectRatio: self::nullableString($row['max_aspect_ratio'] ?? null),
            requiresTransparency: self::bool($row['requires_transparency'] ?? null),
            preferredFormat: self::nullableString($row['preferred_format'] ?? null),
            preferredQuality: self::nullableInt($row['preferred_quality'] ?? null),
            variants: self::nullableString($row['variants'] ?? null),
            createdAt: self::string($row['created_at']),
            updatedAt: self::nullableString($row['updated_at'] ?? null),
        );
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'display_name' => $this->displayName,
            'min_width' => $this->minWidth,
            'min_height' => $this->minHeight,
            'max_width' => $this->maxWidth,
            'max_height' => $this->maxHeight,
            'max_size_bytes' => $this->maxSizeBytes,
            'allowed_extensions' => $this->allowedExtensions,
            'allowed_mime_types' => $this->allowedMimeTypes,
            'is_active' => $this->isActive,
            'notes' => $this->notes,
            'min_aspect_ratio' => $this->minAspectRatio,
            'max_aspect_ratio' => $this->maxAspectRatio,
            'requires_transparency' => $this->requiresTransparency,
            'preferred_format' => $this->preferredFormat,
            'preferred_quality' => $this->preferredQuality,
            'variants' => $this->variants,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
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

        throw ImageProfileInvalidArgumentException::unexpectedType('int field', $value);
    }

    private static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === '1') {
            return true;
        }
        if ($value === 0 || $value === '0') {
            return false;
        }

        throw ImageProfileInvalidArgumentException::unexpectedType('bool field', $value);
    }

    private static function string(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        throw ImageProfileInvalidArgumentException::unexpectedType('string field', $value);
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::string($value);
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return self::int($value);
    }
}
