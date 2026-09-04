<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 02:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Validation\ErrorMapper;

use Maatify\Validation\Enum\AuthErrorCodeEnum;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\ErrorMapper\SystemApiErrorMapper;
use PHPUnit\Framework\TestCase;

final class SystemApiErrorMapperTest extends TestCase
{
    public function testValidationErrorsMapping(): void
    {
        $mapper = new SystemApiErrorMapper();

        $dto = $mapper->mapValidationErrors([
            'email' => [ValidationErrorCodeEnum::INVALID_EMAIL],
        ]);

        self::assertSame(400, $dto->getStatus());
        self::assertSame([
            'code'   => 'INPUT_INVALID',
            'errors' => [
                'email' => ['invalid_email'],
            ],
        ], $dto->toArray());
    }

    public function testAuthErrorMapping(): void
    {
        $mapper = new SystemApiErrorMapper();

        $dto = $mapper->mapAuthError(
            AuthErrorCodeEnum::NOT_AUTHORIZED
        );

        self::assertSame(403, $dto->getStatus());
        self::assertSame(
            AuthErrorCodeEnum::NOT_AUTHORIZED->value,
            $dto->toArray()['code']
        );
    }
}
