<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 02:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Validation\Rules;

use Maatify\Validation\Rules\Primitive\EmailRule;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;

final class EmailRuleTest extends TestCase
{
    public function testValidEmailPasses(): void
    {
        $this->expectNotToPerformAssertions();
        EmailRule::required()->assert('test@example.com');
    }

    public function testInvalidEmailFails(): void
    {
        $this->expectException(ValidationException::class);
        EmailRule::required()->assert('invalid-email');
    }
}
