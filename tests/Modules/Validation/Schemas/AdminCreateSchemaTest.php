<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 02:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use App\Modules\Validation\Schemas\AdminCreateSchema;
use PHPUnit\Framework\TestCase;

final class AdminCreateSchemaTest extends TestCase
{
    public function testValidInput(): void
    {
        $schema = new AdminCreateSchema();

        $result = $schema->validate([
            'display_name' => 'Admin User',
            'email'    => 'admin@test.com',
        ]);

        self::assertTrue($result->isValid());
    }

    public function testInvalidInput(): void
    {
        $schema = new AdminCreateSchema();

        $result = $schema->validate([
            'display_name' => '',
            'email'    => 'bad',
        ]);

        self::assertFalse($result->isValid());

        self::assertContains(
            ValidationErrorCodeEnum::INVALID_DISPLAY_NAME,
            $result->getErrors()['display_name']
        );
    }
}
