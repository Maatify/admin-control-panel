<?php

declare(strict_types=1);

namespace Tests\Modules\Validation\Rules;

use Maatify\Validation\Rules\Semantic\PasswordRule;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;

final class PasswordRuleTest extends TestCase
{
    public function testValidPasswordPasses(): void
    {
        $this->expectNotToPerformAssertions();

        PasswordRule::required()->assert('StrongPass1');
    }

    public function testInvalidPasswordFails(): void
    {
        $this->expectException(ValidationException::class);

        PasswordRule::required()->assert('123');
    }
}
