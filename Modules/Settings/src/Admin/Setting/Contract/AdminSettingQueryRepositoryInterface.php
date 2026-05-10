<?php

declare(strict_types=1);

namespace Maatify\Settings\Admin\Setting\Contract;

use Maatify\Settings\Shared\DTO\SettingDTO;

interface AdminSettingQueryRepositoryInterface
{
    public function findByKey(string $settingKey): ?SettingDTO;

    /**
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<\Maatify\Settings\Shared\DTO\SettingListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(int $page, int $perPage, ?string $globalSearch, array $columnFilters): array;

    /** @return array<string, string> */
    public function listAsKeyValue(): array;
}
