<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;

class SessionRevocationService
{
    private AdminSessionValidationRepositoryInterface $repository;

    public function __construct(AdminSessionValidationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function revoke(string $token): void
    {
        $this->repository->revokeSession($token);
    }

    public function revokeAll(int $adminId): void
    {
        $this->repository->revokeAllSessions($adminId);
    }
}
