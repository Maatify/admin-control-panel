<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\DTO\AppSettingsMetadata;

use JsonSerializable;

/**
 * @phpstan-type AppSettingsMetadataResponseArray array{
 *   groups: AppSettingsGroupMetadataDTO[]
 * }
 */
final readonly class AppSettingsMetadataResponseDTO implements JsonSerializable
{
    /**
     * @param AppSettingsGroupMetadataDTO[] $groups
     */
    public function __construct(
        public array $groups
    ) {
    }

    /**
     * @return AppSettingsMetadataResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'groups' => $this->groups,
        ];
    }
}
