<?php

declare(strict_types=1);

namespace App\Domain\DTO\Admin;

class AdminUpdateRequestDTO
{
    /**
     * @param array<int>|null $roleIds
     */
    public function __construct(
        public readonly ?array $roleIds = null
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $roleIds = null;
        if (array_key_exists('role_ids', $data) && is_array($data['role_ids'])) {
            $roleIds = array_map('intval', $data['role_ids']);
        }

        return new self($roleIds);
    }
}
