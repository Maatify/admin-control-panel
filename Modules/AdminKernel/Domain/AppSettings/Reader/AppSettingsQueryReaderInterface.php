<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\Reader;

use Maatify\AdminKernel\Domain\AppSettings\DTO\AppSettingsListResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface AppSettingsQueryReaderInterface
{
    public function queryAppSettings(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): AppSettingsListResponseDTO;
}
