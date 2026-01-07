<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminListReaderInterface
{
    /**
     * @return array<int, array{id: int, identifier: string}>
     */
    public function getAdmins(): array;
}
