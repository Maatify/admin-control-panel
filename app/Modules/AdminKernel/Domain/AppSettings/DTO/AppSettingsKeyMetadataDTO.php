<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\DTO;

use JsonSerializable;

/**
 * @phpstan-type AppSettingsKeyMetadataArray array{
 *   key: string,
 *   protected: bool,
 *   wildcard: bool,
 *     editable: bool
 * }
 */
final readonly class AppSettingsKeyMetadataDTO implements JsonSerializable
{
    public function __construct(
        public string $key,
        public bool $protected,
        public bool $wildcard = false,
        public bool $editable = true
    )
    {
    }

    /**
     * @return AppSettingsKeyMetadataArray
     */
    public function jsonSerialize(): array
    {
        return [
            'key'       => $this->key,
            'protected' => $this->protected,
            'wildcard'  => $this->wildcard,
            'editable' => $this->editable
        ];
    }
}
