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
                event_key,
                risk_level,
                actor_type,
                actor_id,
                correlation_id,
                request_id,
                route_name,
                ip_address,
                user_agent,
                occurred_at,
                payload
            ) VALUES (
                :event_id,
                :event_key,
                :risk_level,
                :actor_type,
                :actor_id,
                :correlation_id,
                :request_id,
                :route_name,
                :ip_address,
                :user_agent,
                :occurred_at,
                :payload
            )
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);

            $payloadJson = json_encode($dto->payload, JSON_THROW_ON_ERROR);
            $occurredAt = $dto->context->occurredAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');

            $stmt->execute([
                ':event_id' => $dto->eventId,
                ':event_key' => $dto->eventKey,
                ':risk_level' => $dto->riskLevel,
                ':actor_type' => $dto->context->actorType,
                ':actor_id' => $dto->context->actorId,
                ':correlation_id' => $dto->context->correlationId,
                ':request_id' => $dto->context->requestId,
                ':route_name' => $dto->context->routeName,
                ':ip_address' => $dto->context->ipAddress,
                ':user_agent' => $dto->context->userAgent,
                ':occurred_at' => $occurredAt,
                ':payload' => $payloadJson,
            ]);
        } catch (PDOException $e) {
            throw new AuthoritativeAuditStorageException('Outbox write failed: ' . $e->getMessage(), 0, $e);
        } catch (JsonException $e) {
            throw new AuthoritativeAuditStorageException('Payload encoding failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
