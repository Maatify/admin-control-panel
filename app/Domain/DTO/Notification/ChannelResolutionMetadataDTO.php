<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification;

readonly class ChannelResolutionMetadataDTO
{
    /**
     * @param string $resolutionReason Explains why these channels were chosen (e.g., 'admin_preference', 'system_default').
     * @param string $resolutionStrategy The strategy used (e.g., 'strict', 'fallback').
     */
    public function __construct(
        public string $resolutionReason,
        public string $resolutionStrategy
    ) {
    }
}
