<?php

declare(strict_types=1);

namespace Maatify\Settings\Admin\Setting\Service;

use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingCommandRepositoryInterface;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingQueryRepositoryInterface;
use Maatify\Settings\Exception\SettingsInvalidArgumentException;
use Maatify\Settings\Exception\SettingsNotFoundException;
use Maatify\Settings\Shared\DTO\SettingDTO;

final class AdminSettingService
{
    public function __construct(
        private readonly AdminSettingCommandRepositoryInterface $commandRepo,
        private readonly AdminSettingQueryRepositoryInterface $queryRepo,
    ) {}

    public function getByKey(string $settingKey): SettingDTO
    {
        $dto = $this->queryRepo->findByKey($settingKey);

        if ($dto === null) {
            throw SettingsNotFoundException::withKey($settingKey);
        }

        return $dto;
    }

    public function updateValue(UpdateSettingValueCommand $command): void
    {
        $setting = $this->getByKey($command->settingKey);

        if (! $setting->isAdminEditable) {
            throw SettingsInvalidArgumentException::keyNotEditable($command->settingKey);
        }

        $this->validateValueByType($command->settingValue, $setting->valueType);

        if (! $this->commandRepo->updateValue($command)) {
            throw SettingsNotFoundException::withKey($command->settingKey);
        }
    }

    /**
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<\Maatify\Settings\Shared\DTO\SettingListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(int $page, int $perPage, ?string $globalSearch, array $columnFilters): array
    {
        return $this->queryRepo->list($page, $perPage, $globalSearch, $columnFilters);
    }

    /** @return array<string, string> */
    public function listAsKeyValue(): array
    {
        return $this->queryRepo->listAsKeyValue();
    }

    private function validateValueByType(string $value, string $valueType): void
    {
        match ($valueType) {
            'bool' => $this->validateBool($value),
            'int' => $this->validateInt($value),
            'datetime' => $this->validateDateTime($value),
            'date' => $this->validateDate($value),
            'string' => true,
            default => throw SettingsInvalidArgumentException::invalidValueType($valueType),
        };
    }

    private function validateBool(string $value): void
    {
        if ($value !== '0' && $value !== '1') {
            throw SettingsInvalidArgumentException::invalidValueType('bool');
        }
    }

    private function validateInt(string $value): void
    {
        if (! preg_match('/^-?\d+$/', $value)) {
            throw SettingsInvalidArgumentException::invalidValueType('int');
        }
    }

    private function validateDateTime(string $value): void
    {
        try {
            new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw SettingsInvalidArgumentException::invalidValueType('datetime');
        }
    }

    private function validateDate(string $value): void
    {
        try {
            new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw SettingsInvalidArgumentException::invalidValueType('date');
        }
    }
}
