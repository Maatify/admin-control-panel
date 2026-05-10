<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Shared\Service;

use Maatify\Settings\Admin\Setting\Contract\AdminSettingQueryRepositoryInterface;
use Maatify\Settings\Exception\SettingsNotFoundException;
use Maatify\Settings\Shared\DTO\SettingDTO;
use Maatify\Settings\Shared\Service\SettingValueService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SettingValueServiceTest extends TestCase
{
    /** @var AdminSettingQueryRepositoryInterface&MockObject */
    private AdminSettingQueryRepositoryInterface $queryRepo;
    private SettingValueService $service;

    protected function setUp(): void
    {
        $this->queryRepo = $this->createMock(AdminSettingQueryRepositoryInterface::class);
        $this->service = new SettingValueService($this->queryRepo);
    }

    public function testGetValue(): void
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

        $this->queryRepo->method('findByKey')->with('maintenance')->willReturn($dto);

        $result = $this->service->getValue('maintenance');

        self::assertSame('0', $result);
    }

    public function testGetValueNotFound(): void
    {
        $this->queryRepo->method('findByKey')->with('unknown')->willReturn(null);

        $this->expectException(SettingsNotFoundException::class);
        $this->expectExceptionMessage('Setting with key [unknown] not found.');

        $this->service->getValue('unknown');
    }

    public function testGetBoolTrue(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'maintenance',
            settingValue: '1',
            valueType: 'bool',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('maintenance')->willReturn($dto);

        $result = $this->service->getBool('maintenance');

        self::assertTrue($result);
    }

    public function testGetBoolFalse(): void
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

        $this->queryRepo->method('findByKey')->with('maintenance')->willReturn($dto);

        $result = $this->service->getBool('maintenance');

        self::assertFalse($result);
    }

    public function testGetInt(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'default_currency',
            settingValue: '42',
            valueType: 'int',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('default_currency')->willReturn($dto);

        $result = $this->service->getInt('default_currency');

        self::assertSame(42, $result);
    }

    public function testGetString(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'app_name',
            settingValue: 'MyApp',
            valueType: 'string',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('app_name')->willReturn($dto);

        $result = $this->service->getString('app_name');

        self::assertSame('MyApp', $result);
    }

    public function testGetOrDefaultFound(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'ttl',
            settingValue: '30',
            valueType: 'int',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('ttl')->willReturn($dto);

        $result = $this->service->getOrDefault('ttl', 'default_value');

        self::assertSame('30', $result);
    }

    public function testGetOrDefaultNotFound(): void
    {
        $this->queryRepo->method('findByKey')->with('unknown')->willReturn(null);

        $result = $this->service->getOrDefault('unknown', 'default_value');

        self::assertSame('default_value', $result);
    }

    public function testGetOrDefaultBoolFound(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'maintenance',
            settingValue: '1',
            valueType: 'bool',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('maintenance')->willReturn($dto);

        $result = $this->service->getOrDefaultBool('maintenance', false);

        self::assertTrue($result);
    }

    public function testGetOrDefaultBoolNotFound(): void
    {
        $this->queryRepo->method('findByKey')->with('unknown')->willReturn(null);

        $result = $this->service->getOrDefaultBool('unknown', true);

        self::assertTrue($result);
    }

    public function testGetOrDefaultIntFound(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'ttl',
            settingValue: '15',
            valueType: 'int',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('ttl')->willReturn($dto);

        $result = $this->service->getOrDefaultInt('ttl', 30);

        self::assertSame(15, $result);
    }

    public function testGetOrDefaultIntNotFound(): void
    {
        $this->queryRepo->method('findByKey')->with('unknown')->willReturn(null);

        $result = $this->service->getOrDefaultInt('unknown', 30);

        self::assertSame(30, $result);
    }
}
