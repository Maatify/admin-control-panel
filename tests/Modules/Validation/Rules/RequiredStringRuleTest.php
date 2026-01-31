<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 02:31
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Validation\Rules;

use Maatify\Validation\Rules\RequiredStringRule;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;

final class RequiredStringRuleTest extends TestCase
{
    public function testValidStringPasses(): void
    {
        $this->expectNotToPerformAssertions();
        RequiredStringRule::rule(3, 10)->assert('valid');
    }

    public function testTooShortStringFails(): void
    {
        $this->expectException(ValidationException::class);
        RequiredStringRule::rule(3, 10)->assert('ab');
    }
}
