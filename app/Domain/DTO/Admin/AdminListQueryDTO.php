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
        $page = isset($data['page']) && is_numeric($data['page']) ? (int)$data['page'] : 1;
        $perPage = isset($data['per_page']) && is_numeric($data['per_page']) ? (int)$data['per_page'] : 20;
        $filters = isset($data['filters']) && is_array($data['filters']) ? $data['filters'] : [];

        return new self($page, $perPage, $filters);
    }
}
