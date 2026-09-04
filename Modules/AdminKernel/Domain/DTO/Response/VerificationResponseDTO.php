<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Response;

use Maatify\AdminKernel\Domain\Enum\VerificationStatus;
use JsonSerializable;

readonly class VerificationResponseDTO implements JsonSerializable
{
    public function __construct(
        public int $adminId,
        public int $emailId,
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
            'email_id' => $this->emailId,
            'verification_status' => $this->status->value,
        ];
    }
}
