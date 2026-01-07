<?php

declare(strict_types=1);

namespace App\Domain\DTO\Admin;

class AdminCreateRequestDTO
{
    /**
     * @param array<int> $roleIds
     */
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly array $roleIds = []
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['email'] ?? ''),
            (string)($data['password'] ?? ''),
            isset($data['role_ids']) && is_array($data['role_ids'])
                ? array_map('intval', $data['role_ids'])
                : []
        );
    }
}
