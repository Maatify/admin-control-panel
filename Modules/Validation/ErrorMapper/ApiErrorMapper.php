<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\ErrorMapper;

use Maatify\Validation\Contracts\ErrorMapperInterface;
use Maatify\Validation\Enum\HttpStatusCodeEnum;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;

final class ApiErrorMapper implements ErrorMapperInterface
{
    /**
     * @param ValidationErrorCodeEnum  $errors
     * @param array<string, list<ValidationErrorCodeEnum>> $errors
     *
     * @return array{status:int, body:array<string, mixed>}
     */
    public function map(array $errors): array
    {
        $mapped = [];

        foreach ($errors as $field => $codes) {
            foreach ($codes as $code) {
                $mapped[$field][] = $code->value;
            }
        }

        return [
            'status' => HttpStatusCodeEnum::BAD_REQUEST->value,
            'body' => [
                'code' => 'INPUT_INVALID',
                'errors' => $mapped,
            ],
        ];
    }
}