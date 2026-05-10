<?php

declare(strict_types=1);

namespace Maatify\Settings\Admin\Setting\Contract;

use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;

interface AdminSettingCommandRepositoryInterface
{
    public function updateValue(UpdateSettingValueCommand $command): bool;
}
