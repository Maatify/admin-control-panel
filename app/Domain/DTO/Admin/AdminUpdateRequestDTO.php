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
            $roleIds = [];
            foreach ($data['role_ids'] as $id) {
                if (is_int($id) || is_numeric($id)) {
                    $roleIds[] = (int)$id;
                }
            }
        }

        return new self($roleIds);
    }
}
