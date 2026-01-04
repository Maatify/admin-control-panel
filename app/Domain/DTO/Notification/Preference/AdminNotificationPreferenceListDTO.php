<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification\Preference;

readonly class AdminNotificationPreferenceListDTO implements \JsonSerializable
{
    /**
     * @param array<AdminNotificationPreferenceDTO> $preferences
     */
    public function __construct(
        public array $preferences
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'preferences' => $this->preferences,
        ];
    }
}
