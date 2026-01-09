<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 19:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\DTO\Common;
use JsonSerializable;

/**
 * @phpstan-type PaginationArray array{
 *   page: int,
 *   per_page: int,
 *   total: int
 * }
 */
final class PaginationDTO implements JsonSerializable
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total
    ) {}

    /**
     * @return PaginationArray
     */
    public function jsonSerialize(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
        ];
    }
}
