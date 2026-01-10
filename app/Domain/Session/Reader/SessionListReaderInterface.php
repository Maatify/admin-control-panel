<?php

declare(strict_types=1);

namespace App\Domain\Session\Reader;

use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\DTO\Session\SessionListResponseDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;

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
