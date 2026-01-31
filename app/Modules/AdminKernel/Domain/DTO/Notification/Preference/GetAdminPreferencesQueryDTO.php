<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Notification\Preference;

readonly class GetAdminPreferencesQueryDTO
{
    public function __construct(
        public int $adminId
    ) {
    }
}
