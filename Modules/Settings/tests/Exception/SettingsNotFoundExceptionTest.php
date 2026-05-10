<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Exception;

use Maatify\Settings\Exception\SettingsNotFoundException;
use Maatify\Settings\Exception\SettingsExceptionInterface;
use PHPUnit\Framework\TestCase;

final class SettingsNotFoundExceptionTest extends TestCase
{
    public function testWithKey(): void
    {
        $exception = SettingsNotFoundException::withKey('maintenance');

        self::assertInstanceOf(SettingsNotFoundException::class, $exception);
        self::assertInstanceOf(SettingsExceptionInterface::class, $exception);
        self::assertSame('Setting with key [maintenance] not found.', $exception->getMessage());
    }

    public function testWithKeyEmptyString(): void
    {
        $exception = SettingsNotFoundException::withKey('');

        self::assertSame('Setting with key [] not found.', $exception->getMessage());
    }

    public function testWithKeySpecialCharacters(): void
    {
        $exception = SettingsNotFoundException::withKey('setting_with_special!@#');

        self::assertSame('Setting with key [setting_with_special!@#] not found.', $exception->getMessage());
    }
}
