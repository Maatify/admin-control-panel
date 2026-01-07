<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminRepositoryInterface
{
    public function create(): int;
    public function getCreatedAt(int $id): string;
}
