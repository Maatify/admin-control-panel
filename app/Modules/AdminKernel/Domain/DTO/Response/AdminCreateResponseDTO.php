<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-19 11:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Response;

use JsonSerializable;

final readonly class AdminCreateResponseDTO implements JsonSerializable
{
    public function __construct(
        public int $adminId,
        public string $createdAt,
        public string $tempPassword // one-time only
    )
    {
    }

    /**
     * @return array<string, string|int>
     */
    public function jsonSerialize(): array
    {
        return [
            'admin_id'      => $this->adminId,
            'created_at'    => $this->createdAt,
            'temp_password' => $this->tempPassword,
        ];
    }
}
