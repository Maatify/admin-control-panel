<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Admin\AdminListQueryDTO;
use App\Domain\DTO\Admin\AdminListResponseDTO;

interface AdminQueryReaderInterface
{
    public function getAdmins(AdminListQueryDTO $query): AdminListResponseDTO;
}
