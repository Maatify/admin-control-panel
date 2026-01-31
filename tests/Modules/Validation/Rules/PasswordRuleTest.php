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

use Maatify\Validation\Rules\PasswordRule;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;

final class PasswordRuleTest extends TestCase
{
    public function testValidPasswordPasses(): void
    {
        $this->expectNotToPerformAssertions();
        PasswordRule::rule()->assert('StrongPass1');
    }

    public function testInvalidPasswordFails(): void
    {
        $this->expectException(ValidationException::class);
        PasswordRule::rule()->assert('123');
    }
}
