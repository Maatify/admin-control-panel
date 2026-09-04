<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Notification;

use Maatify\AdminKernel\Domain\Notification\NotificationChannelType;

readonly class ChannelResolutionResultDTO
{
    /**
     * @param NotificationChannelType[] $resolvedChannels Ordered list of channels to use.
     * @param ChannelResolutionMetadataDTO $metadata Metadata explaining the resolution.
     * @param bool $isNoChannelAvailable True if no channels could be resolved.
     */
    public function __construct(
        public array $resolvedChannels,
        public ChannelResolutionMetadataDTO $metadata,
        public bool $isNoChannelAvailable
    ) {
    }
}
