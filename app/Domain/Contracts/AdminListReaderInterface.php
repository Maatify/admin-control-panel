<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AdminList\AdminListQueryDTO;
use App\Domain\DTO\AdminList\AdminListResponseDTO;

interface AdminListReaderInterface
{
    /**
     * @return array<int, array{id: int, identifier: string}>
     */
    public function getAdmins(): array;

    public function listAdmins(AdminListQueryDTO $query): AdminListResponseDTO;
}
