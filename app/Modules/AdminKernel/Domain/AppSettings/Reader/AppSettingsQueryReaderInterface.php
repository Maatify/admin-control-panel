<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\Reader;

use Maatify\AdminKernel\Domain\DTO\AppSettingsList\AppSettingsListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface AppSettingsQueryReaderInterface
{
    public function queryAppSettings(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): AppSettingsListResponseDTO;
}
