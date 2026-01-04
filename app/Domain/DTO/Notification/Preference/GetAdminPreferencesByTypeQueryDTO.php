<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification\Preference;

readonly class GetAdminPreferencesByTypeQueryDTO
{
    public function __construct(
        public int $adminId,
        public string $notificationType
    ) {
    }
}
