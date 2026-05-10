<?php

declare(strict_types=1);

namespace Maatify\Settings\Admin\Setting\Command;

use Maatify\Settings\Exception\SettingsInvalidArgumentException;

final readonly class UpdateSettingValueCommand
{
    public function __construct(
        public string $settingKey,
        public string $settingValue,
    ) {
        if (trim($settingKey) === '') {
            throw SettingsInvalidArgumentException::emptyField('settingKey');
        }
    }
}
