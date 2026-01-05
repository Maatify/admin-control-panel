<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\RecoveryLockException;

class RecoveryStateService
{
    public function isLocked(): bool
    {
        if (($_ENV['RECOVERY_MODE'] ?? 'false') === 'true') {
            return true;
        }

        $key = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
        // Basic length check for security
        if (empty($key) || strlen($key) < 32) {
            return true;
        }

        return false;
    }

    public function check(): void
    {
        if ($this->isLocked()) {
            throw new RecoveryLockException("System is in Recovery-Locked Mode.");
        }
    }
}
