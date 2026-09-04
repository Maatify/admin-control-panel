<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\StepUpGrant;
use Maatify\AdminKernel\Domain\Enum\Scope;

interface StepUpGrantRepositoryInterface
{
    public function save(StepUpGrant $grant): void;

    public function find(int $adminId, string $sessionId, Scope $scope): ?StepUpGrant;

    public function revoke(int $adminId, string $sessionId, Scope $scope): void;

    public function revokeAll(int $adminId): void;
}
