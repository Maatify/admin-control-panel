<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation\DTO;

use DateTimeImmutable;
use Maatify\Crypto\KeyRotation\CryptoKeyInterface;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;

/**
 * CryptoKeyDTO
 *
 * Immutable data transfer object representing a cryptographic key.
 *
 * IMPORTANT:
 * - This DTO contains RAW key material.
 * - MUST NOT be logged or serialized insecurely.
 * - No crypto logic exists here.
 */
final readonly class CryptoKeyDTO implements CryptoKeyInterface
{
    public function __construct(
        private string $id,
        private string $material,
        private KeyStatusEnum $status,
        private DateTimeImmutable $createdAt
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function material(): string
    {
        return $this->material;
    }

    public function status(): KeyStatusEnum
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function withStatus(KeyStatusEnum $status): CryptoKeyInterface
    {
        return new self(
            id: $this->id,
            material: $this->material,
            status: $status,
            createdAt: $this->createdAt
        );
    }
}
