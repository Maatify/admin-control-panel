<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Exception;

use Maatify\Settings\Exception\SettingsInvalidArgumentException;
use Maatify\Settings\Exception\SettingsExceptionInterface;
use PHPUnit\Framework\TestCase;

final class SettingsInvalidArgumentExceptionTest extends TestCase
{
    public function testEmptyField(): void
    {
        $exception = SettingsInvalidArgumentException::emptyField('settingKey');

        self::assertInstanceOf(SettingsInvalidArgumentException::class, $exception);
        self::assertInstanceOf(SettingsExceptionInterface::class, $exception);
        self::assertSame('Field [settingKey] must not be empty.', $exception->getMessage());
    }

    public function testKeyNotEditable(): void
    {
        $exception = SettingsInvalidArgumentException::keyNotEditable('system_id');

        self::assertSame('Setting key [system_id] is not editable from admin UI.', $exception->getMessage());
    }

    public function testInvalidValueType(): void
    {
        $exception = SettingsInvalidArgumentException::invalidValueType('custom');

        self::assertSame('Invalid value type [custom]. Allowed: bool, int, string, datetime, date.', $exception->getMessage());
    }
}
