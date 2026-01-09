<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:25
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Validation\Exceptions;

use App\Modules\Validation\DTO\ValidationResultDTO;
use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use RuntimeException;

final class ValidationFailedException extends RuntimeException
{
    public function __construct(
        private readonly ValidationResultDTO $result
    ) {
        parent::__construct('Validation failed.');
    }

    /**
     * @return array<string, list<ValidationErrorCodeEnum>>
     */
    public function getErrors(): array
    {
        return $this->result->getErrors();
    }
}
