<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Response;

use JsonSerializable;

final readonly class AdminTemporaryPasswordResetResponseDTO implements JsonSerializable
{
    public function __construct(
        public int $adminId,
        public string $tempPassword
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function jsonSerialize(): array
    {
        return [
            'admin_id' => $this->adminId,
            'temp_password' => $this->tempPassword,
        ];
    }
}

