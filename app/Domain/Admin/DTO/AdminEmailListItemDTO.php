<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-24 12:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Admin\DTO;

use App\Domain\Enum\VerificationStatus;
use JsonSerializable;

final readonly class AdminEmailListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $emailId,
        public string $email,
        public VerificationStatus $status,
        public ?string $verifiedAt
    ) {}

    /**
     * @return array<string, string|int|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'email_id'    => $this->emailId,
            'email'       => $this->email,
            'status'      => $this->status->value,
            'verified_at' => $this->verifiedAt,
        ];
    }
}
