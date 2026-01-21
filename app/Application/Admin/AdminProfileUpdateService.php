<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-21 16:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Admin;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Admin\Enum\AdminStatusEnum;
use App\Domain\Service\SessionRevocationService;
use App\Domain\Support\CorrelationId;
use PDO;
use RuntimeException;
use DateTimeImmutable;

final readonly class AdminProfileUpdateService
{
    public function __construct(
        private PDO $pdo,
        private AdminActivityLogService $adminActivityLogService,
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private SessionRevocationService $sessionRevocationService
    ) {
    }

    /**
     * Update admin profile fields (partial update).
     *
     * Rules:
     * - Only changed fields are persisted
     * - Activity + Audit logs only fire if something actually changed
     *
     * @param array{
     *   display_name?: string|null,
     *   status?: AdminStatusEnum|string
     * } $input
     */
    public function update(
        AdminContext $adminContext,
        RequestContext $requestContext,
        int $targetAdminId,
        array $input
    ): void
    {
        $this->pdo->beginTransaction();

        try {
            $current = $this->fetchCurrentState($targetAdminId);

            $changes = [];

            // ─────────────────────────────
            // Display name
            // ─────────────────────────────
            if (array_key_exists('display_name', $input)) {
                $newName = $input['display_name'];

                if ($newName !== $current['display_name']) {
                    $changes['display_name'] = $newName;
                }
            }

            // ─────────────────────────────
            // Status
            // ─────────────────────────────
            if (array_key_exists('status', $input)) {
                $newStatus = $input['status'];

                if (is_string($newStatus)) {
                    $newStatus = AdminStatusEnum::from($newStatus);
                }

                if ($newStatus !== $current['status']) {
                    $changes['status'] = $newStatus;
                }
            }

            // ─────────────────────────────
            // No-op protection
            // ─────────────────────────────
            if ($changes === []) {
                $this->pdo->rollBack();
                return;
            }

            $correlationId = CorrelationId::generate();

            // ─────────────────────────────
            // Persist
            // ─────────────────────────────
            $this->applyChanges($targetAdminId, $changes);

            // ─────────────────────────────
            // Activity Log (non-authoritative)
            // ─────────────────────────────
            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: 'admin.profile.updated',
                entityType: 'admin',
                entityId: $targetAdminId,
                metadata: [
                    'fields_changed' => array_keys($changes),
                ]
            );

            // ─────────────────────────────
            // Authoritative Audit (MUST be inside TX)
            // ─────────────────────────────
            $this->auditWriter->write(
                new AuditEventDTO(
                    actor_id: $adminContext->adminId,
                    action: 'admin_profile_updated',
                    target_type: 'admin',
                    target_id: $targetAdminId,
                    risk_level: 'MEDIUM',
                    payload: [
                        'fields_changed' => array_keys($changes),
                    ],
                    correlation_id: $correlationId,
                    request_id: $requestContext->requestId,
                    created_at: new DateTimeImmutable()
                )
            );

            $statusChanged =
                isset($changes['status']) &&
                in_array(
                    $changes['status'],
                    [AdminStatusEnum::SUSPENDED, AdminStatusEnum::DISABLED],
                    true
                );

            if ($statusChanged) {
                $this->sessionRevocationService->revokeAllActiveForAdmin(
                    targetAdminId: $targetAdminId,
                    actorAdminId: $adminContext->adminId,
                    context: $requestContext,
                    reason: 'admin_status_changed_to_' . $changes['status']->value
                );
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ─────────────────────────────
    // Helpers
    // ─────────────────────────────

    /**
     * @return array{
     *   display_name: string|null,
     *   status: AdminStatusEnum
     * }
     */
    private function fetchCurrentState(int $adminId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT display_name, status FROM admins WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $adminId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Admin not found');
        }

        /** @var array{display_name: string|null, status: string} $row */

        assert(is_string($row['status']));

        return [
            'display_name' => is_string($row['display_name']) ? $row['display_name'] : null,
            'status' => AdminStatusEnum::from($row['status']),
        ];
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function applyChanges(int $adminId, array $changes): void
    {
        $sets = [];
        $params = ['id' => $adminId];

        foreach ($changes as $field => $value) {
            $sets[] = sprintf('%s = :%s', $field, $field);

            $params[$field] = $value instanceof AdminStatusEnum
                ? $value->value
                : $value;
        }

        $sql = sprintf(
            'UPDATE admins SET %s WHERE id = :id',
            implode(', ', $sets)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}
