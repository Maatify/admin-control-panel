<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:50
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Contracts;

use Maatify\Validation\DTO\ApiErrorResponseDTO;
use Maatify\Validation\Enum\AuthErrorCodeEnum;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;

interface SystemErrorMapperInterface
{
    /**
     * @param array<string, list<\Maatify\Validation\Enum\ValidationErrorCodeEnum>> $errors
     */
    public function mapValidationErrors(array $errors): ApiErrorResponseDTO;

    public function mapAuthError(AuthErrorCodeEnum $errorCode): ApiErrorResponseDTO;
}
