<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Password\DTO;

use App\Modules\Crypto\Password\Exception\InvalidArgonPolicyException;

final readonly class ArgonPolicyDTO
{
    public function __construct(
        public int $memoryCost,
        public int $timeCost,
        public int $threads
    )
    {
        if ($memoryCost <= 0) {
            throw new InvalidArgonPolicyException('Argon memoryCost must be > 0');
        }

        if ($timeCost <= 0) {
            throw new InvalidArgonPolicyException('Argon timeCost must be > 0');
        }

        if ($threads <= 0) {
            throw new InvalidArgonPolicyException('Argon threads must be > 0');
        }
    }

    /**
     * @return array<string, int>
     */
    public function toNativeOptions(): array
    {
        return [
            'memory_cost' => $this->memoryCost,
            'time_cost'   => $this->timeCost,
            'threads'     => $this->threads,
        ];
    }
}
