<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Infrastructure\Mysql;

use Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface;
use Maatify\SecuritySignals\DTO\SecuritySignalRecordDTO;
use Maatify\SecuritySignals\Exception\SecuritySignalsStorageException;
use PDO;
use PDOException;
use JsonException;

class SecuritySignalsLoggerMysqlRepository implements SecuritySignalsLoggerInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function write(SecuritySignalRecordDTO $record): void
    {
        $sql = <<<SQL
            INSERT INTO security_signals (
                event_id,
                actor_type,
                actor_id,
                signal_type,
                severity,
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
                :signal_type,
                :severity,
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
                'signal_type' => $record->signalType,
                'severity' => $record->severity,
                'correlation_id' => $record->correlationId,
                'request_id' => $record->requestId,
                'route_name' => $record->routeName,
                'ip_address' => $record->ipAddress,
                'user_agent' => $record->userAgent,
                'metadata' => json_encode($record->metadata, JSON_THROW_ON_ERROR),
                'occurred_at' => $record->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException | JsonException $e) {
            throw new SecuritySignalsStorageException(
                message: "Failed to persist security signal record: " . $e->getMessage(),
                previous: $e
            );
        }
    }
}
