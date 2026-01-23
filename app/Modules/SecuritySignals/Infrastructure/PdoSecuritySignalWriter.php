<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Infrastructure;

use Maatify\SecuritySignals\Contract\SecuritySignalLoggerInterface;
use Maatify\SecuritySignals\DTO\SecuritySignalDTO;
use Maatify\SecuritySignals\Exception\SecuritySignalWriteException;
use PDO;
use PDOException;

class PdoSecuritySignalWriter implements SecuritySignalLoggerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(SecuritySignalDTO $dto): void
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
                ':event_id' => $dto->event_id,
                ':actor_type' => $dto->context->actor_type,
                ':actor_id' => $dto->context->actor_id,
                ':signal_type' => $dto->signal_type->value,
                ':severity' => $dto->severity->value,
                ':correlation_id' => $dto->context->correlation_id,
                ':request_id' => $dto->context->request_id,
                ':route_name' => $dto->context->route_name,
                ':ip_address' => $dto->context->ip_address,
                ':user_agent' => $dto->context->user_agent,
                ':metadata' => json_encode($dto->metadata, JSON_THROW_ON_ERROR),
                ':occurred_at' => $dto->context->occurred_at->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException $e) {
            throw new SecuritySignalWriteException('Failed to write security signal to database', 0, $e);
        } catch (\JsonException $e) {
            throw new SecuritySignalWriteException('Failed to encode metadata for security signal', 0, $e);
        }
    }
}
