<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionDetailsDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionRoleListItemDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionAdminOverrideListItemDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use PDO;

final readonly class PdoPermissionDetailsRepository implements PermissionDetailsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getPermissionById(int $permissionId): PermissionDetailsDTO
    {
        $stmt = $this->pdo->prepare(
            '
            SELECT
                id,
                name,
                display_name,
                description,
                created_at
            FROM permissions
            WHERE id = :id
            '
        );

        $stmt->execute(['id' => $permissionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new EntityNotFoundException('permission', $permissionId);
        }

        /**
         * @var  array{
         *   id:int,
         *   name:string,
         *   display_name:string|null,
         *   description:string|null,
         *   created_at:string
         * }$row
         */
        $group = explode('.', $row['name'], 2)[0];

        return new PermissionDetailsDTO(
            id: (int) $row['id'],
            name: (string) $row['name'],
            group: $group,
            display_name: $row['display_name'],
            description: $row['description'],
            created_at: (string) $row['created_at'],
        );
    }
}
