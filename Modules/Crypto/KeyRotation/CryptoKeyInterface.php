<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:31
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation;

use DateTimeImmutable;

/**
 * CryptoKeyInterface
 *
 * Immutable representation of a cryptographic key.
 *
 * IMPORTANT:
 * - This interface does NOT expose how the key is stored.
 * - It does NOT perform cryptographic operations.
 * - It represents key identity + policy metadata only.
 */
interface CryptoKeyInterface
{
    /**
     * Immutable key identifier.
     */
    public function id(): string;

    /**
     * Raw binary key material.
     *
     * WARNING:
     * - MUST be handled carefully
     * - MUST NOT be logged
     */
    public function material(): string;

    /**
     * Current lifecycle status of the key.
     */
    public function status(): KeyStatusEnum;

    /**
     * Creation timestamp (for audit / ordering).
     */
    public function createdAt(): DateTimeImmutable;

    /**
     * Return a NEW key instance with a different status.
     *
     * This is required to keep keys immutable while still allowing
     * providers to perform lifecycle transitions.
     */
    public function withStatus(KeyStatusEnum $status): self;
}
