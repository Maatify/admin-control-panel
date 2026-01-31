<?php

declare(strict_types=1);

namespace Tests\Modules\Validation\Validator;

use Maatify\Validation\Schemas\AdminCreateSchema;
use Maatify\Validation\Validator\RespectValidator;
use PHPUnit\Framework\TestCase;

final class RespectValidatorTest extends TestCase
{
    public function testDelegatesToSchema(): void
    {
        $validator = new RespectValidator();
        $schema = new AdminCreateSchema();

        $result = $validator->validate($schema, [
            'name'     => '',      // invalid (required, min 1)
            'email'    => 'bad',   // invalid email
            'password' => 'bad',   // invalid password per PasswordRule
        ]);

        self::assertFalse($result->isValid());
    }
}
