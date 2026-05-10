<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Admin\Setting\Service;

use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingCommandRepositoryInterface;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingQueryRepositoryInterface;
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\Settings\Exception\SettingsInvalidArgumentException;
use Maatify\Settings\Exception\SettingsNotFoundException;
use Maatify\Settings\Shared\DTO\SettingDTO;
use Maatify\Settings\Shared\DTO\SettingListItemDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AdminSettingServiceTest extends TestCase
{
    /** @var AdminSettingQueryRepositoryInterface&MockObject */
    private AdminSettingQueryRepositoryInterface $queryRepo;
    /** @var AdminSettingCommandRepositoryInterface&MockObject */
    private AdminSettingCommandRepositoryInterface $commandRepo;
    private AdminSettingService $service;

    protected function setUp(): void
    {
        $this->queryRepo = $this->createMock(AdminSettingQueryRepositoryInterface::class);
        $this->commandRepo = $this->createMock(AdminSettingCommandRepositoryInterface::class);
        $this->service = new AdminSettingService($this->commandRepo, $this->queryRepo);
    }

    public function testGetByKeySuccess(): void
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

        $this->queryRepo->method('findByKey')->with('maintenance')->willReturn($dto);

        $result = $this->service->getByKey('maintenance');

        self::assertSame($dto, $result);
    }

    public function testGetByKeyNotFound(): void
    {
        $this->queryRepo->method('findByKey')->with('unknown')->willReturn(null);

        $this->expectException(SettingsNotFoundException::class);
        $this->expectExceptionMessage('Setting with key [unknown] not found.');

        $this->service->getByKey('unknown');
    }

    public function testUpdateValueSuccess(): void
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

        $command = new UpdateSettingValueCommand('maintenance', '1');
        $this->commandRepo
            ->expects(self::once())
            ->method('updateValue')
            ->with($command)
            ->willReturn(true);

        $this->service->updateValue($command);
    }

    public function testUpdateValueNotEditable(): void
    {
        $dto = new SettingDTO(
            id: 1,
            settingKey: 'system_id',
            settingValue: '123',
            valueType: 'int',
            isAdminEditable: false,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('system_id')->willReturn($dto);

        $this->expectException(SettingsInvalidArgumentException::class);
        $this->expectExceptionMessage('Setting key [system_id] is not editable from admin UI.');

        $command = new UpdateSettingValueCommand('system_id', '456');
        $this->service->updateValue($command);
    }

    public function testUpdateValueNotFound(): void
    {
        $this->queryRepo->method('findByKey')->with('unknown')->willReturn(null);

        $this->expectException(SettingsNotFoundException::class);

        $command = new UpdateSettingValueCommand('unknown', '1');
        $this->service->updateValue($command);
    }

    public function testUpdateValueRepositoryFails(): void
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
        $this->commandRepo->method('updateValue')->willReturn(false);

        $this->expectException(SettingsNotFoundException::class);

        $command = new UpdateSettingValueCommand('maintenance', '1');
        $this->service->updateValue($command);
    }

    public function testList(): void
    {
        $item = new SettingListItemDTO(1, 'maintenance', '0', 'bool', true, null, '2026-05-11 10:00:00');
        $result = [
            'data' => [$item],
            'pagination' => [
                'page' => 1,
                'per_page' => 20,
                'total' => 1,
                'filtered' => 1,
            ],
        ];

        $this->queryRepo->method('list')->willReturn($result);

        $actual = $this->service->list(1, 20, null, []);

        self::assertSame($result, $actual);
    }

    public function testListAsKeyValue(): void
    {
        $expected = [
            'maintenance' => '0',
            'default_currency' => '1',
        ];

        $this->queryRepo->method('listAsKeyValue')->willReturn($expected);

        $result = $this->service->listAsKeyValue();

        self::assertSame($expected, $result);
    }

    public function testUpdateValueBoolValidationSuccess(): void
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
        $this->commandRepo->method('updateValue')->willReturn(true);

        $command = new UpdateSettingValueCommand('maintenance', '1');
        $this->service->updateValue($command);

        self::assertTrue(true);
    }

    public function testUpdateValueBoolValidationFails(): void
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

        $this->expectException(SettingsInvalidArgumentException::class);

        $command = new UpdateSettingValueCommand('maintenance', 'invalid');
        $this->service->updateValue($command);
    }

    public function testUpdateValueIntValidationSuccess(): void
    {
        $dto = new SettingDTO(
            id: 2,
            settingKey: 'default_currency',
            settingValue: '1',
            valueType: 'int',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('default_currency')->willReturn($dto);
        $this->commandRepo->method('updateValue')->willReturn(true);

        $command = new UpdateSettingValueCommand('default_currency', '42');
        $this->service->updateValue($command);

        self::assertTrue(true);
    }

    public function testUpdateValueIntValidationFails(): void
    {
        $dto = new SettingDTO(
            id: 2,
            settingKey: 'default_currency',
            settingValue: '1',
            valueType: 'int',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('default_currency')->willReturn($dto);

        $this->expectException(SettingsInvalidArgumentException::class);

        $command = new UpdateSettingValueCommand('default_currency', 'abc');
        $this->service->updateValue($command);
    }

    public function testUpdateValueStringNoValidation(): void
    {
        $dto = new SettingDTO(
            id: 3,
            settingKey: 'app_name',
            settingValue: 'App',
            valueType: 'string',
            isAdminEditable: true,
            adminNote: null,
            createdAt: '2026-05-11 10:00:00',
            updatedAt: '2026-05-11 10:00:00',
        );

        $this->queryRepo->method('findByKey')->with('app_name')->willReturn($dto);
        $this->commandRepo->method('updateValue')->willReturn(true);

        $command = new UpdateSettingValueCommand('app_name', 'any value');
        $this->service->updateValue($command);

        self::assertTrue(true);
    }
}
