<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Domain\Security\Password\PasswordPepperRing;

class PasswordService
{
    /**
     * @param PasswordPepperRing $ring The authoritative pepper ring
     * @param array{memory_cost: int, time_cost: int, threads: int} $argonOptions Configured Argon2id options
     */
    public function __construct(
        private readonly PasswordPepperRing $ring,
        private readonly array $argonOptions
    ) {
    }

    /**
     * Hashes a password using the currently active pepper and configured Argon2id options.
     *
     * @return array{hash: string, pepper_id: string}
     */
    public function hash(string $plain): array
    {
        $pepper = $this->ring->activeSecret();
        $peppered = hash_hmac('sha256', $plain, $pepper);
        $hash = password_hash($peppered, PASSWORD_ARGON2ID, $this->argonOptions);

        return [
            'hash' => $hash,
            'pepper_id' => $this->ring->activeId()
        ];
    }

    /**
     * Verifies a password against a stored hash using the specific pepper ID.
     *
     * @param string $plain The plaintext password
     * @param string $hash The stored hash
     * @param string $pepperId The ID of the pepper used to create the hash
     * @return bool True if valid
     */
    public function verify(string $plain, string $hash, string $pepperId): bool
    {
        try {
            $pepper = $this->ring->secret($pepperId);
        } catch (\RuntimeException) {
            // Fail closed if pepper ID is unknown (cannot verify)
            return false;
        }

        $peppered = hash_hmac('sha256', $plain, $pepper);

        return password_verify($peppered, $hash);
    }

    /**
     * Checks if the password needs rehash due to pepper rotation OR Argon2 parameter changes.
     */
    public function needsRehash(string $hash, string $pepperId): bool
    {
        // 1. Check Pepper Rotation
        if ($pepperId !== $this->ring->activeId()) {
            return true;
        }

        // 2. Check Argon2 Parameter Rotation
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, $this->argonOptions);
    }
}
