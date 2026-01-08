<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 09:57
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Reversible\Registry;

use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmInterface;
use App\Modules\Crypto\Reversible\Exceptions\CryptoAlgorithmNotSupportedException;

/**
 * ReversibleCryptoAlgorithmRegistry
 *
 * Central registry that binds allowed reversible crypto algorithms
 * (defined by ReversibleCryptoAlgorithmEnum) to their concrete implementations.
 *
 * SECURITY RULES:
 * - Every registered algorithm MUST correspond to a ReversibleCryptoAlgorithmEnum case.
 * - Algorithms NOT registered here are considered unsupported and MUST fail-closed.
 * - This registry is the ONLY extension point for adding new algorithms.
 */
final class ReversibleCryptoAlgorithmRegistry
{
    /**
     * @var array<string, ReversibleCryptoAlgorithmInterface>
     */
    private array $algorithms = [];

    /**
     * Register a reversible crypto algorithm implementation.
     *
     * @throws CryptoAlgorithmNotSupportedException
     *         If the algorithm enum is already registered.
     */
    public function register(ReversibleCryptoAlgorithmInterface $algorithm): void
    {
        $key = $algorithm->algorithm()->value;

        if (isset($this->algorithms[$key])) {
            throw new CryptoAlgorithmNotSupportedException(
                sprintf('Crypto algorithm already registered: %s', $key)
            );
        }

        $this->algorithms[$key] = $algorithm;
    }

    /**
     * Retrieve a registered reversible crypto algorithm by enum.
     *
     * @throws CryptoAlgorithmNotSupportedException
     *         If the algorithm is not registered.
     */
    public function get(ReversibleCryptoAlgorithmEnum $algorithm): ReversibleCryptoAlgorithmInterface
    {
        $key = $algorithm->value;

        return $this->algorithms[$key]
               ?? throw new CryptoAlgorithmNotSupportedException(
                sprintf('Unsupported crypto algorithm: %s', $key)
            );
    }

    /**
     * Check whether a reversible crypto algorithm is registered.
     */
    public function has(ReversibleCryptoAlgorithmEnum $algorithm): bool
    {
        return isset($this->algorithms[$algorithm->value]);
    }

    /**
     * Returns all registered algorithm identifiers.
     *
     * @return list<string>
     */
    public function list(): array
    {
        return array_keys($this->algorithms);
    }
}
