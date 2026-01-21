<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\TelemetryLoggerInterface;
use App\Domain\DTO\TelemetryDTO;
use App\Domain\Exception\Telemetry\TelemetryMappingException;
use App\Domain\Exception\Telemetry\TelemetryStorageException;
use PDO;
use PDOException;
use Throwable;

class PdoTelemetryLogger implements TelemetryLoggerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(TelemetryDTO $event): void
    {
        try {
            $changesJson = json_encode($event->changes, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new TelemetryMappingException('Failed to encode telemetry changes: ' . $e->getMessage(), 0, $e);
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_logs (actor_admin_id, target_type, target_id, action, changes, ip_address, user_agent, occurred_at)
             VALUES (:actor_admin_id, :target_type, :target_id, :action, :changes, :ip_address, :user_agent, :occurred_at)'
            );

            $stmt->execute([
                ':actor_admin_id' => $event->actorAdminId,
                ':target_type'    => $event->targetType,
                ':target_id'      => $event->targetId,
                ':action'         => $event->action,
                ':changes'        => $changesJson,
                ':ip_address'     => $event->ipAddress,
                ':user_agent'     => $event->userAgent,
                ':occurred_at'    => $event->occurredAt->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            throw new TelemetryStorageException('Failed to write telemetry: ' . $e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new TelemetryStorageException('Unexpected error writing telemetry: ' . $e->getMessage(), 0, $e);
        }
    }
}
