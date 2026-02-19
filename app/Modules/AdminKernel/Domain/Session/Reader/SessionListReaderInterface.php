<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Session\Reader;

use Maatify\AdminKernel\Domain\DTO\Session\SessionListResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface SessionListReaderInterface
{
    /**
     * @param   ListQueryDTO         $query
     * @param   ResolvedListFilters  $filters
     * @param   int|null             $adminIdFilter
     * @param   string               $currentSessionHash
     *
     * @return SessionListResponseDTO
     */
    public function getSessions(
        ListQueryDTO $query,
        ResolvedListFilters $filters,
        ?int $adminIdFilter,
        string $currentSessionHash
    ): SessionListResponseDTO;
}
