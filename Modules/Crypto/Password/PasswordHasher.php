<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\Password;

use Maatify\Crypto\Password\DTO\ArgonPolicyDTO;
use Maatify\Crypto\Password\Exception\HashingFailedException;
use Maatify\Crypto\Password\Exception\PepperUnavailableException;
use Maatify\Crypto\Password\Pepper\PasswordPepperProviderInterface;

final readonly class PasswordHasher implements PasswordHasherInterface
{

    public function __construct(
        private PasswordPepperProviderInterface $pepperProvider,
        private ArgonPolicyDTO $argonPolicy
    ) {

    }

    /**
     * @throws PepperUnavailableException
     * @throws HashingFailedException
     */
    public function hash(string $plain): string
    {
        $pepper = $this->pepperProvider->getPepper();

        if ($pepper === '') {
            throw new PepperUnavailableException('Password pepper is empty');
        }

        // HMAC (pepper) → Argon (canonical)
        $peppered = hash_hmac('sha256', $plain, $pepper, true);

        $hash = password_hash(
            $peppered,
            PASSWORD_ARGON2ID,
            $this->argonPolicy->toNativeOptions()
        );

        if (!is_string($hash)) {
            throw new HashingFailedException('Argon2id hashing failed');
        }

        return $hash;
    }

    /**
     * @throws PepperUnavailableException
     */
    public function verify(string $plain, string $storedHash): bool
    {
        $pepper = $this->pepperProvider->getPepper();

        if ($pepper === '') {
            throw new PepperUnavailableException('Password pepper is empty');
        }

        $peppered = hash_hmac('sha256', $plain, $pepper, true);

        return password_verify($peppered, $storedHash);
    }

    public function needsRehash(string $storedHash): bool
    {
        return password_needs_rehash(
            $storedHash,
            PASSWORD_ARGON2ID,
            $this->argonPolicy->toNativeOptions()
        );
    }
}
