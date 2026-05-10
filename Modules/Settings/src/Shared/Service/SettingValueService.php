<?php

declare(strict_types=1);

namespace Maatify\Settings\Shared\Service;

use Maatify\Settings\Admin\Setting\Contract\AdminSettingQueryRepositoryInterface;
use Maatify\Settings\Exception\SettingsNotFoundException;

final class SettingValueService
{
    public function __construct(private readonly AdminSettingQueryRepositoryInterface $queryRepo) {}

    public function getValue(string $settingKey): string
    {
        $dto = $this->queryRepo->findByKey($settingKey);

        if ($dto === null) {
            throw SettingsNotFoundException::withKey($settingKey);
        }

        return $dto->settingValue;
    }

    public function getBool(string $settingKey): bool
    {
        $value = $this->getValue($settingKey);
        return (int) $value === 1;
    }

    public function getInt(string $settingKey): int
    {
        $value = $this->getValue($settingKey);
        return (int) $value;
    }

    public function getString(string $settingKey): string
    {
        return $this->getValue($settingKey);
    }

    public function getOrDefault(string $settingKey, string $default): string
    {
        try {
            return $this->getValue($settingKey);
        } catch (SettingsNotFoundException) {
            return $default;
        }
    }

    public function getOrDefaultBool(string $settingKey, bool $default): bool
    {
        try {
            return $this->getBool($settingKey);
        } catch (SettingsNotFoundException) {
            return $default;
        }
    }

    public function getOrDefaultInt(string $settingKey, int $default): int
    {
        try {
            return $this->getInt($settingKey);
        } catch (SettingsNotFoundException) {
            return $default;
        }
    }
}
