<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\AppSettingsMetadata;

use JsonSerializable;

/**
 * @phpstan-type AppSettingsKeyMetadataArray array{
 *   key: string,
 *   protected: bool,
 *   wildcard: bool
 * }
 */
final readonly class AppSettingsKeyMetadataDTO implements JsonSerializable
{
    public function __construct(
        public string $key,
        public bool $protected,
        public bool $wildcard = false
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
        ];
    }
}
