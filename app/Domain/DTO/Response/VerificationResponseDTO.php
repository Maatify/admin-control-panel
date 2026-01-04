<?php

declare(strict_types=1);

namespace App\Domain\DTO\Response;

use App\Domain\Enum\VerificationStatus;
use JsonSerializable;

readonly class VerificationResponseDTO implements JsonSerializable
{
    public function __construct(
        public int $adminId,
        public VerificationStatus $status
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'admin_id' => $this->adminId,
            'verification_status' => $this->status->value,
        ];
    }
}
