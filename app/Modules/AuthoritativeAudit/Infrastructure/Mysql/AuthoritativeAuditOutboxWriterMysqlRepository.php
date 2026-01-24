<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Infrastructure\Mysql;

use DateTimeZone;
use Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface;
use Maatify\AuthoritativeAudit\DTO\AuthoritativeAuditOutboxWriteDTO;
use Maatify\AuthoritativeAudit\Exception\AuthoritativeAuditStorageException;
use PDO;
use PDOException;
use JsonException;

class AuthoritativeAuditOutboxWriterMysqlRepository implements AuthoritativeAuditOutboxWriterInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function write(AuthoritativeAuditOutboxWriteDTO $dto): void
    {
        $sql = <<<SQL
            INSERT INTO authoritative_audit_outbox (
                event_id,
                actor_type,
                actor_id,
                action,
                target_type,
                target_id,
                risk_level,
                payload,
                correlation_id,
                created_at
            ) VALUES (
                :event_id,
                :actor_type,
                :actor_id,
                :action,
                :target_type,
                :target_id,
                :risk_level,
                :payload,
                :correlation_id,
                :created_at
            )
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);

            $payloadJson = json_encode($dto->payload, JSON_THROW_ON_ERROR);
            $createdAt = $dto->createdAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');

            $stmt->execute([
                ':event_id' => $dto->eventId,
                ':actor_type' => $dto->actorType,
                ':actor_id' => $dto->actorId,
                ':action' => $dto->action,
                ':target_type' => $dto->targetType,
                ':target_id' => $dto->targetId,
                ':risk_level' => $dto->riskLevel,
                ':payload' => $payloadJson,
                ':correlation_id' => $dto->correlationId,
                ':created_at' => $createdAt,
            ]);
        } catch (PDOException $e) {
            throw new AuthoritativeAuditStorageException('Outbox write failed: ' . $e->getMessage(), 0, $e);
        } catch (JsonException $e) {
            throw new AuthoritativeAuditStorageException('Payload encoding failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
