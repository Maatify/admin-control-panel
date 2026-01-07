<?php

declare(strict_types=1);

namespace App\Domain\DTO\Admin;

use DateTimeImmutable;
use JsonSerializable;

class AdminListItemDTO implements JsonSerializable
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $identifier,
        public readonly string $verificationStatus,
        public readonly DateTimeImmutable $createdAt,
        public readonly array $roles,
        public readonly bool $isSystemOwner
    ) {}

    /**
     * @return array{
     *     id: int,
     *     identifier: string,
     *     verification_status: string,
     *     created_at: string,
     *     roles: array<int, string>,
     *     is_system_owner: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'verification_status' => $this->verificationStatus,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'roles' => $this->roles,
            'is_system_owner' => $this->isSystemOwner,
        ];
    }
}
