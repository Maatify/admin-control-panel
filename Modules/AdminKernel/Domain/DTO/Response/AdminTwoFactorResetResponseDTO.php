<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Response;

use JsonSerializable;

final readonly class AdminTwoFactorResetResponseDTO implements JsonSerializable
{
    public function __construct(
        public int $adminId,
        public bool $twoFactorReset
    ) {
    }

    /**
     * @return array<string, int|bool>
     */
    public function jsonSerialize(): array
    {
        return [
            'admin_id' => $this->adminId,
            'two_factor_reset' => $this->twoFactorReset,
        ];
    }
}

