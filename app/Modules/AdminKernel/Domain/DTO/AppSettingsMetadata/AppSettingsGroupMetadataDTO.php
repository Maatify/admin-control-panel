<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\AppSettingsMetadata;

use JsonSerializable;

/**
 * @phpstan-type AppSettingsGroupMetadataArray array{
 *   name: string,
 *   label: string,
 *   keys: AppSettingsKeyMetadataDTO[]
 * }
 */
final readonly class AppSettingsGroupMetadataDTO implements JsonSerializable
{
    /**
     * @param AppSettingsKeyMetadataDTO[] $keys
     */
    public function __construct(
        public string $name,
        public string $label,
        public array $keys
    ) {
    }

    /**
     * @return AppSettingsGroupMetadataArray
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'keys' => $this->keys,
        ];
    }
}
