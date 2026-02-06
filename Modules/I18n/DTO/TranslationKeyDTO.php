<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

/**
 * Represents a canonical translation key record.
 *
 * Structured, library-grade.
 * No parsing.
 * No derived strings.
 */
final readonly class TranslationKeyDTO
{
    public function __construct(
        public int $id,
        public string $scope,
        public string $domain,
        public string $key,
        public ?string $description,
        public string $createdAt,
    ) {
    }
}
