<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Infrastructure\Mysql;

use DateTimeInterface;
use Maatify\AuditTrail\Contract\AuditTrailLoggerInterface;
use Maatify\AuditTrail\DTO\AuditTrailRecordDTO;
use Maatify\AuditTrail\Exception\AuditTrailStorageException;
use PDO;
use PDOException;
use JsonException;

class AuditTrailLoggerMysqlRepository implements AuditTrailLoggerInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function write(AuditTrailRecordDTO $record): void
    {
        $sql = <<<SQL
            INSERT INTO audit_trail (
                event_id,
                actor_type,
                actor_id,
                event_key,
                entity_type,
                entity_id,
                subject_type,
                subject_id,
                referrer_route_name,
                referrer_path,
                referrer_host,
                correlation_id,
                request_id,
                route_name,
                ip_address,
                user_agent,
                metadata,
                occurred_at
            ) VALUES (
                :event_id,
                :actor_type,
                :actor_id,
                :event_key,
                :entity_type,
                :entity_id,
                :subject_type,
                :subject_id,
                :referrer_route_name,
                :referrer_path,
                :referrer_host,
                :correlation_id,
                :request_id,
                :route_name,
                :ip_address,
                :user_agent,
                :metadata,
                :occurred_at
            )
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                'event_id' => $record->eventId,
                'actor_type' => $record->actorType,
                'actor_id' => $record->actorId,
                'event_key' => $record->eventKey,
                'entity_type' => $record->entityType,
                'entity_id' => $record->entityId,
                'subject_type' => $record->subjectType,
                'subject_id' => $record->subjectId,
                'referrer_route_name' => $record->referrerRouteName,
                'referrer_path' => $record->referrerPath,
                'referrer_host' => $record->referrerHost,
                'correlation_id' => $record->correlationId,
                'request_id' => $record->requestId,
                'route_name' => $record->routeName,
                'ip_address' => $record->ipAddress,
                'user_agent' => $record->userAgent,
                'metadata' => json_encode($record->metadata, JSON_THROW_ON_ERROR),
                'occurred_at' => $record->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException | JsonException $e) {
            throw new AuditTrailStorageException(
                message: "Failed to persist audit trail record: " . $e->getMessage(),
                previous: $e
            );
        }
    }
}
