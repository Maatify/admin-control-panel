<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Password;

use RuntimeException;

final readonly class PasswordPepperRing
{
    /**
     * @param array<string, string> $peppers Map of pepper_id => secret
     * @param string $activeId The ID of the pepper to use for new hashes
     */
    public function __construct(
        private array $peppers,
        private string $activeId
    ) {
        if (empty($this->peppers)) {
            throw new RuntimeException('Password Peppers must be configured.');
        }
        if (!isset($this->peppers[$this->activeId])) {
            throw new RuntimeException("Active Pepper ID '$this->activeId' not found in configured peppers.");
        }
        foreach ($this->peppers as $id => $secret) {
            if (strlen($secret) < 32) {
                throw new RuntimeException("Pepper secret for ID '$id' is too short (min 32 chars).");
            }
        }
    }

    public function activeId(): string
    {
        return $this->activeId;
    }

    public function activeSecret(): string
    {
        return $this->peppers[$this->activeId];
    }

    public function secret(string $id): string
    {
        if (!isset($this->peppers[$id])) {
            throw new RuntimeException("Pepper ID '$id' not found in ring.");
        }
        return $this->peppers[$id];
    }
}
