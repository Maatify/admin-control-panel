<?php

declare(strict_types=1);

namespace Tests\Modules\AppSettings\Unit;

use Maatify\AppSettings\AppSettingsService;
use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\Enum\AppSettingValueTypeEnum;
use Maatify\AppSettings\Exception\DuplicateAppSettingException;
use Maatify\AppSettings\Exception\InvalidAppSettingException;
use Maatify\AppSettings\Policy\AppSettingsProtectionPolicy;
use Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy;
use Maatify\AppSettings\Repository\AppSettingsRepositoryInterface;
use PDOException;
use PHPUnit\Framework\TestCase;

final class AppSettingsServiceTest extends TestCase
{
    private $repository;
    private AppSettingsWhitelistPolicy $whitelist;
    private AppSettingsProtectionPolicy $protection;
    private AppSettingsService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AppSettingsRepositoryInterface::class);
        $this->whitelist = new AppSettingsWhitelistPolicy(['grp' => ['key']]);
        $this->protection = new AppSettingsProtectionPolicy([]);

        $this->service = new AppSettingsService(
            $this->repository,
            $this->whitelist,
            $this->protection
        );
    }

    public function testGetTypedReturnsInt(): void
    {
        $this->repository->expects($this->once())
            ->method('findOne')
            ->with('grp', 'key', true)
            ->willReturn(['setting_value' => '123', 'setting_type' => 'int']);

        $result = $this->service->getTyped('grp', 'key');
        $this->assertSame(123, $result);
    }

    public function testGetTypedReturnsBool(): void
    {
        $this->repository->expects($this->once())
            ->method('findOne')
            ->willReturn(['setting_value' => 'true', 'setting_type' => 'bool']);

        $this->assertTrue($this->service->getTyped('grp', 'key'));
    }

    public function testGetTypedReturnsJson(): void
    {
        $this->repository->expects($this->once())
            ->method('findOne')
            ->willReturn(['setting_value' => '{"a":1}', 'setting_type' => 'json']);

        $this->assertSame(['a' => 1], $this->service->getTyped('grp', 'key'));
    }

    public function testCreateValidatesIntFailure(): void
    {
        $this->expectException(InvalidAppSettingException::class);
        $this->expectExceptionMessage('valid integer');

        $this->service->create(new AppSettingDTO(
            'grp', 'key', 'not-int', AppSettingValueTypeEnum::INT
        ));
    }

    public function testCreateValidatesBoolFailure(): void
    {
        $this->expectException(InvalidAppSettingException::class);
        $this->expectExceptionMessage('valid boolean');

        $this->service->create(new AppSettingDTO(
            'grp', 'key', 'maybe', AppSettingValueTypeEnum::BOOL
        ));
    }

    public function testCreateValidatesJsonFailure(): void
    {
        $this->expectException(InvalidAppSettingException::class);
        $this->expectExceptionMessage('valid JSON');

        $this->service->create(new AppSettingDTO(
            'grp', 'key', '{invalid', AppSettingValueTypeEnum::JSON
        ));
    }

    public function testCreateHandlesDuplicate(): void
    {
        $dto = new AppSettingDTO('grp', 'key', '123', AppSettingValueTypeEnum::INT);

        $pdoException = new PDOException('Duplicate entry', 23000);

        $this->repository->expects($this->once())
            ->method('insert')
            ->willThrowException($pdoException);

        $this->expectException(DuplicateAppSettingException::class);
        $this->service->create($dto);
    }

    public function testUpdateValidatesValueWithType(): void
    {
        $this->repository->expects($this->once())
            ->method('findOne')
            ->willReturn(['setting_type' => 'int', 'setting_value' => '123']);

        $this->expectException(InvalidAppSettingException::class);

        $this->service->update(new AppSettingUpdateDTO(
            'grp', 'key', 'not-int', null
        ));
    }

    public function testUpdateValidatesValueWithNewType(): void
    {
        $this->repository->expects($this->once())
            ->method('findOne')
            ->willReturn(['setting_type' => 'int', 'setting_value' => '123']);

        $this->expectException(InvalidAppSettingException::class);

        $this->service->update(new AppSettingUpdateDTO(
            'grp', 'key', '123', AppSettingValueTypeEnum::BOOL
        ));
    }
}
