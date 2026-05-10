<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Shared\DTO;

use Maatify\Settings\Shared\DTO\SettingListItemDTO;
use PHPUnit\Framework\TestCase;

final class SettingListItemDTOTest extends TestCase
{
    public function testConstruction(): void
    {
        $dto = new SettingListItemDTO(
            id: 1,
            settingKey: 'maintenance',
            settingValue: '0',
            valueType: 'bool',
            isAdminEditable: true,
            adminNote: 'Note',
            updatedAt: '2026-05-11 10:00:00',
        );

        self::assertSame(1, $dto->id);
        self::assertSame('maintenance', $dto->settingKey);
        self::assertTrue($dto->isAdminEditable);
    }

    public function testJsonSerialize(): void
    {
        $dto = new SettingListItemDTO(
            id: 1,
            settingKey: 'maintenance',
            settingValue: '0',
            valueType: 'bool',
            isAdminEditable: true,
            adminNote: null,
            updatedAt: '2026-05-11 10:00:00',
        );

        $json = $dto->jsonSerialize();

        self::assertIsArray($json);
        self::assertArrayNotHasKey('created_at', $json);
        self::assertArrayHasKey('updated_at', $json);
    }
}
