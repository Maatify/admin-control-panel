<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Shared\DTO;

use Maatify\Settings\Shared\DTO\SettingDTO;
use PHPUnit\Framework\TestCase;

final class SettingDTOTest extends TestCase
{
    public function testConstruction(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'maintenance',
            settingValue: '0',
            valueType: 'bool',
            isAdminEditable: true,
            adminNote: 'App maintenance mode',
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        self::assertSame(1, $dto->id);
        self::assertSame('maintenance', $dto->settingKey);
        self::assertSame('0', $dto->settingValue);
        self::assertSame('bool', $dto->valueType);
        self::assertTrue($dto->isAdminEditable);
        self::assertSame('App maintenance mode', $dto->adminNote);
    }

    public function testJsonSerialize(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'maintenance',
            settingValue: '0',
            valueType: 'bool',
            isAdminEditable: true,
            adminNote: 'Note',
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $json = $dto->jsonSerialize();

        self::assertIsArray($json);
        /** @var array<string, mixed> $json */
        self::assertSame(1, $json['id']);
        self::assertSame('maintenance', $json['setting_key']);
        self::assertSame('0', $json['setting_value']);
        self::assertSame('bool', $json['value_type']);
        self::assertTrue($json['is_admin_editable']);
    }

    public function testJsonSerializeWithoutAdminNote(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'maintenance',
            settingValue: '0',
            valueType: 'bool',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $json = $dto->jsonSerialize();

        /** @var array<string, mixed> $json */
        self::assertNull($json['admin_note']);
    }
}
