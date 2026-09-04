<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-24 14:02
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Admin\Reader;

use Maatify\AdminKernel\Domain\Admin\Reader\AdminBasicInfoReaderInterface;
use PDO;

final class PDOAdminBasicInfoReader implements AdminBasicInfoReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getDisplayName(int $adminId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT display_name FROM admins WHERE id = :id LIMIT 1"
        );

        $stmt->execute(['id' => $adminId]);

        $result = $stmt->fetchColumn();

        return $result !== false ? (string) $result : null;
    }
}
