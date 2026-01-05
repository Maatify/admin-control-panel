<?php

declare(strict_types=1);

namespace App\Domain\Service;

use RuntimeException;

class PasswordService
{
    public function __construct(
        private readonly string $pepper
    ) {
        if ($this->pepper === '') {
            throw new RuntimeException('Password Pepper must be configured.');
        }
    }

    public function hash(string $plain): string
    {
        $peppered = hash_hmac('sha256', $plain, $this->pepper);
        return password_hash($peppered, PASSWORD_ARGON2ID);
    }

    public function verify(string $plain, string $hash): bool
    {
        // 1. Try Hardened (Peppered + Argon2id)
        $peppered = hash_hmac('sha256', $plain, $this->pepper);
        if (password_verify($peppered, $hash)) {
            return true;
        }

        // 2. Legacy Fallback (No Pepper, likely Bcrypt)
        // STRICT: We do not auto-rehash here to maintain frozen auth behavior/state.
        if (password_verify($plain, $hash)) {
            return true;
        }

        return false;
    }
}
