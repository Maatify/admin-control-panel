<?php

declare(strict_types=1);

namespace Maatify\Settings\Admin\Setting\Infrastructure\Repository;

use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Admin\Setting\Contract\AdminSettingCommandRepositoryInterface;
use PDO;

final class PdoAdminSettingCommandRepository implements AdminSettingCommandRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function updateValue(UpdateSettingValueCommand $command): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `settings` SET `setting_value` = :setting_value WHERE `setting_key` = :setting_key'
        );
        $stmt->execute([
            'setting_key'   => $command->settingKey,
            'setting_value' => $command->settingValue,
        ]);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        $checkStmt = $this->pdo->prepare('SELECT COUNT(*) FROM `settings` WHERE `setting_key` = :setting_key');
        $checkStmt->execute(['setting_key' => $command->settingKey]);
        $exists = (int) $checkStmt->fetchColumn() > 0;

        return $exists;
    }
}
