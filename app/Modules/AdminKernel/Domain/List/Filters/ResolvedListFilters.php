<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 23:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List\Filters;

use DateTimeImmutable;

final readonly class ResolvedListFilters
{
    /**
     * @param   array<string, string>  $columnFilters
     */
    public function __construct(
        public ?string $globalSearch,
        public array $columnFilters,
        public ?DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
    )
    {
    }
}
