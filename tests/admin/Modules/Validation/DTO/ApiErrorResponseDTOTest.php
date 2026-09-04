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

namespace Tests\Modules\Validation\DTO;

use Maatify\Validation\DTO\ApiErrorResponseDTO;
use PHPUnit\Framework\TestCase;

final class ApiErrorResponseDTOTest extends TestCase
{
    public function testToArray(): void
    {
        $dto = new ApiErrorResponseDTO(
            status: 400,
            code  : 'INPUT_INVALID',
            errors: [
                'email' => ['invalid_email'],
            ]
        );

        self::assertSame(400, $dto->getStatus());
        self::assertSame([
            'code'   => 'INPUT_INVALID',
            'errors' => [
                'email' => ['invalid_email'],
            ],
        ], $dto->toArray());
    }
}
