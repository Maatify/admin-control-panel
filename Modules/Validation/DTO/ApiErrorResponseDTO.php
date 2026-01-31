<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\DTO;

final readonly class ApiErrorResponseDTO
{
    /**
     * @param   array<string, list<string>>  $errors
     */
    public function __construct(
        private int $status,
        private string $code,
        private array $errors = []
    )
    {
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array{code:string, errors:array<string, list<string>>}
     */
    public function toArray(): array
    {
        return [
            'code'   => $this->code,
            'errors' => $this->errors,
        ];
    }
}
