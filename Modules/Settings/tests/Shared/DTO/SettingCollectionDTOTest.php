<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Shared\DTO;

use Maatify\Settings\Shared\DTO\SettingCollectionDTO;
use Maatify\Settings\Shared\DTO\SettingListItemDTO;
use PHPUnit\Framework\TestCase;

final class SettingCollectionDTOTest extends TestCase
{
    public function testConstruction(): void
    {
        $items = [
            new SettingListItemDTO(1, 'key1', 'value1', 'string', true, null, '2026-05-11'),
            new SettingListItemDTO(2, 'key2', 'value2', 'int', false, null, '2026-05-11'),
        ];

        $collection = new SettingCollectionDTO($items);

        self::assertCount(2, $collection);
    }

    public function testIteration(): void
    {
        $items = [
            new SettingListItemDTO(1, 'key1', 'value1', 'string', true, null, '2026-05-11'),
            new SettingListItemDTO(2, 'key2', 'value2', 'int', false, null, '2026-05-11'),
        ];

        $collection = new SettingCollectionDTO($items);
        $keys = [];

        foreach ($collection as $item) {
            $keys[] = $item->settingKey;
        }

        self::assertSame(['key1', 'key2'], $keys);
    }

    public function testJsonSerialize(): void
    {
        $items = [
            new SettingListItemDTO(1, 'key1', 'value1', 'string', true, null, '2026-05-11'),
        ];

        $collection = new SettingCollectionDTO($items);
        $json = $collection->jsonSerialize();

        self::assertIsArray($json);
        self::assertCount(1, $json);
        /** @var mixed $item */
        $item = $json[0];
        self::assertInstanceOf(SettingListItemDTO::class, $item);
        self::assertSame('key1', $item->settingKey);
    }

    public function testEmptyCollection(): void
    {
        $collection = new SettingCollectionDTO([]);

        self::assertCount(0, $collection);
        self::assertSame([], $collection->jsonSerialize());
    }
}
