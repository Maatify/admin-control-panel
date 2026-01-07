<?php

declare(strict_types=1);

namespace App\Domain\DTO\Admin;

class AdminListQueryDTO
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public readonly int $page = 1,
        public readonly int $per_page = 20,
        public readonly array $filters = []
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['page'] ?? 1),
            (int)($data['per_page'] ?? 20),
            isset($data['filters']) && is_array($data['filters']) ? $data['filters'] : []
        );
    }
}
