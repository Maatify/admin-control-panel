<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification\Preference;

readonly class GetAdminPreferencesQueryDTO
{
    public function __construct(
        public int $adminId
    ) {
    }
}
