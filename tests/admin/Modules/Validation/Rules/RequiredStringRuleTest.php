<?php

declare(strict_types=1);

namespace Tests\Modules\Validation\Rules;

use Maatify\Validation\Rules\Primitive\StringRule;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;

final class RequiredStringRuleTest extends TestCase
{
    public function testValidStringPasses(): void
    {
        $this->expectNotToPerformAssertions();

        StringRule::required(3, 10)->assert('valid');
    }

    public function testTooShortStringFails(): void
    {
        $this->expectException(ValidationException::class);

        StringRule::required(3, 10)->assert('ab');
    }
}
