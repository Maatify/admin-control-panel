<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Admin\Setting\Command;

use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Exception\SettingsInvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UpdateSettingValueCommandTest extends TestCase
{
    public function testValidConstruction(): void
    {
        $command = new UpdateSettingValueCommand('maintenance', '1');

        self::assertSame('maintenance', $command->settingKey);
        self::assertSame('1', $command->settingValue);
    }

    public function testEmptySettingKey(): void
    {
        $this->expectException(SettingsInvalidArgumentException::class);
        $this->expectExceptionMessage('Field [settingKey] must not be empty.');

        new UpdateSettingValueCommand('', '1');
    }

    public function testEmptySettingValueAllowed(): void
    {
        $command = new UpdateSettingValueCommand('maintenance', '');
        self::assertSame('', $command->settingValue);
    }

    public function testWhitespaceOnlyKey(): void
    {
        $this->expectException(SettingsInvalidArgumentException::class);

        new UpdateSettingValueCommand('   ', '1');
    }

    public function testWhitespaceSettingValueAllowed(): void
    {
        $command = new UpdateSettingValueCommand('maintenance', '   ');
        self::assertSame('   ', $command->settingValue);
    }
}
