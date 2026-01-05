<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\StepUpGrant;
use App\Domain\Enum\Scope;

interface StepUpGrantRepositoryInterface
{
    public function save(StepUpGrant $grant): void;

    public function find(int $adminId, string $sessionId, Scope $scope): ?StepUpGrant;

    public function revoke(int $adminId, string $sessionId, Scope $scope): void;
}
