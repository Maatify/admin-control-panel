<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

/**
 * Represents resolved translations for a single (language + scope + domain).
 *
 * Structure:
 * [
 *   'page.title' => '...',
 *   'button.save' => '...'
 * ]
 */
final readonly class TranslationDomainValuesDTO
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(
        public array $values
    ) {}

    public function get(string $keyPart): ?string
    {
        return $this->values[$keyPart] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->values;
    }
}
