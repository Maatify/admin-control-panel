<?php

declare(strict_types=1);

namespace Maatify\Crypto\DX;

use Maatify\AdminKernel\Domain\Service\PasswordService;
use Maatify\Crypto\Reversible\ReversibleCryptoService;

/**
 * CryptoProvider
 *
 * Unified Developer Experience (DX) Facade for Cryptography.
 *
 * Provides a single injection point for:
 * 1. Context-based encryption (HKDF Pipeline)
 * 2. Direct encryption (No-HKDF Pipeline)
 * 3. Password hashing (Password Pipeline)
 *
 * @internal This is a DX helper.
 */
final readonly class CryptoProvider
{
    public function __construct(
        private CryptoContextFactory $contextFactory,
        private CryptoDirectFactory $directFactory,
        private PasswordService $passwordService
    ) {
    }

    /**
     * Get a crypto service bound to a specific context.
     * Uses HKDF to derive keys from the root rotation keys.
     *
     * Pipeline: KeyRotation -> HKDF -> ReversibleCrypto
     *
     * @param string $context Explicit context string (e.g. "notification:email:v1")
     * @return ReversibleCryptoService
     */
    public function context(string $context): ReversibleCryptoService
    {
        return $this->contextFactory->create($context);
    }

    /**
     * Get a direct crypto service using raw root keys.
     * WARNING: Does not provide domain separation.
     *
     * Pipeline: KeyRotation -> ReversibleCrypto
     *
     * @return ReversibleCryptoService
     */
    public function direct(): ReversibleCryptoService
    {
        return $this->directFactory->create();
    }

    /**
     * Access the password hashing service.
     *
     * Pipeline: HMAC(Pepper) -> Argon2id
     *
     * @return PasswordService
     */
    public function password(): PasswordService
    {
        return $this->passwordService;
    }
}
