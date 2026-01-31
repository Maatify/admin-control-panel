<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 23:48
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

use DateTimeImmutable;
use Exception;

final readonly class ListQueryDTO
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $globalSearch,
        /** @var array<string, string> */
        public array $columnFilters,
        public ?DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
    )
    {
    }

    /**
     * @param   array{
     *   page?: int,
     *   per_page?: int,
     *   search?: array{
     *     global?: string,
     *     columns?: array<string, string>
     *   },
     *   date?: array{
     *     from?: string,
     *     to?: string
     *   }
     * }  $input
     *
     * @throws Exception
     */
    public static function fromArray(array $input): self
    {
        $page = max(1, (int)($input['page'] ?? 1));
        $perPage = max(1, (int)($input['per_page'] ?? 20));

        $search = $input['search'] ?? [];

        $global = isset($search['global']) && trim((string)$search['global']) !== ''
            ? trim((string)$search['global'])
            : null;

        $columns = is_array($search['columns'] ?? null)
            ? array_filter(
                $search['columns'],
                static fn($v): bool => is_string($v) && trim($v) !== ''
            )
            : [];

        $date = $input['date'] ?? [];

        $dateFrom = isset($date['from'])
            ? new DateTimeImmutable($date['from'])
            : null;

        $dateTo = isset($date['to'])
            ? new DateTimeImmutable($date['to'])
            : null;

        return new self(
            $page,
            $perPage,
            $global,
            $columns,
            $dateFrom,
            $dateTo
        );
    }
}
