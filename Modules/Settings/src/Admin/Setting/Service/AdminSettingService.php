<?php

declare(strict_types=1);

namespace Maatify\Settings\Admin\Setting\Service;

use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingCommandRepositoryInterface;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingQueryRepositoryInterface;
use Maatify\Settings\Exception\SettingsInvalidArgumentException;
use Maatify\Settings\Exception\SettingsNotFoundException;
use Maatify\Settings\Shared\Contract\SettingValueTypeProviderInterface;
use Maatify\Settings\Shared\DTO\SettingDTO;
use Maatify\Settings\Shared\Infrastructure\DefaultSettingValueTypeProvider;

final class AdminSettingService
{
    public function __construct(
        private readonly AdminSettingCommandRepositoryInterface $commandRepo,
        private readonly AdminSettingQueryRepositoryInterface $queryRepo,
        private readonly SettingValueTypeProviderInterface $typeProvider = new DefaultSettingValueTypeProvider(),
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

        $this->typeProvider->validate($command->settingValue, $setting->valueType);

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
}
