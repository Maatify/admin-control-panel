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
        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : '';
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';

        $roleIds = [];
        if (isset($data['role_ids']) && is_array($data['role_ids'])) {
            foreach ($data['role_ids'] as $id) {
                if (is_int($id) || is_numeric($id)) {
                    $roleIds[] = (int)$id;
                }
            }
        }

        return new self(
            $email,
            $password,
            $roleIds
        );
    }
}
