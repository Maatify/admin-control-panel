<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AdminActivityDTO;

interface AdminActivityQueryInterface
{
    /**
     * @param int $adminId
     * @param int $limit
     * @return AdminActivityDTO[]
     */
    public function findByActor(int $adminId, int $limit = 20): array;

    /**
     * @param string $targetType
     * @param int $targetId
     * @param int $limit
     * @return AdminActivityDTO[]
     */
    public function findByTarget(string $targetType, int $targetId, int $limit = 20): array;
}
