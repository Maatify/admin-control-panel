<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Infrastructure\Mysql;

use Maatify\BehaviorTrace\Contract\BehaviorTracePolicyInterface;
use Maatify\BehaviorTrace\Contract\BehaviorTraceQueryInterface;
use Maatify\BehaviorTrace\DTO\BehaviorTraceContextDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceCursorDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceEventDTO;
use Maatify\BehaviorTrace\Exception\BehaviorTraceStorageException;
use Maatify\BehaviorTrace\Recorder\BehaviorTraceDefaultPolicy;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use Exception;
use JsonException;

class BehaviorTraceQueryMysqlRepository implements BehaviorTraceQueryInterface
{
    private const TABLE_NAME = 'operational_activity';

    private readonly BehaviorTracePolicyInterface $policy;

    public function __construct(
        private readonly PDO $pdo,
        ?BehaviorTracePolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new BehaviorTraceDefaultPolicy();
    }

    /**
     * @return iterable<BehaviorTraceEventDTO>
     */
    public function read(?BehaviorTraceCursorDTO $cursor, int $limit = 100): iterable
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE 1=1',
            self::TABLE_NAME
        );

        $params = [];

        if ($cursor) {
            $sql .= ' AND (occurred_at > :last_occurred_at OR (occurred_at = :last_occurred_at_eq AND id > :last_id))';
            $params[':last_occurred_at'] = $cursor->lastOccurredAt->format('Y-m-d H:i:s.u');
            $params[':last_occurred_at_eq'] = $cursor->lastOccurredAt->format('Y-m-d H:i:s.u');
            $params[':last_id'] = $cursor->lastId;
        }

        $sql .= ' ORDER BY occurred_at ASC, id ASC LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @var array<string, mixed> $row */
                yield $this->mapRowToDTO($row);
            }

        } catch (PDOException $e) {
            throw new BehaviorTraceStorageException('Failed to read behavior trace: ' . $e->getMessage(), 0, $e);
        } catch (Exception $e) {
            throw new BehaviorTraceStorageException('Failed to map behavior trace row: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return BehaviorTraceEventDTO
     * @throws Exception
     */
    private function mapRowToDTO(array $row): BehaviorTraceEventDTO
    {
        $actorTypeStr = is_string($row['actor_type'] ?? null) ? $row['actor_type'] : 'ANONYMOUS';
        $actorType = $this->policy->normalizeActorType($actorTypeStr);

        $metadata = null;
        if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
            try {
                $decoded = json_decode($row['metadata'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            } catch (JsonException) {
                $metadata = null;
            }
        }

        $occurredAtStr = is_string($row['occurred_at'] ?? null) ? $row['occurred_at'] : '1970-01-01 00:00:00';
        $eventId = is_string($row['event_id'] ?? null) ? $row['event_id'] : '';
        $action = is_string($row['action'] ?? null) ? $row['action'] : 'unknown';

        $context = new BehaviorTraceContextDTO(
            actorType: $actorType,
            actorId: is_int($row['actor_id'] ?? null) ? $row['actor_id'] : null,
            correlationId: is_string($row['correlation_id'] ?? null) ? $row['correlation_id'] : null,
            requestId: is_string($row['request_id'] ?? null) ? $row['request_id'] : null,
            routeName: is_string($row['route_name'] ?? null) ? $row['route_name'] : null,
            ipAddress: is_string($row['ip_address'] ?? null) ? $row['ip_address'] : null,
            userAgent: is_string($row['user_agent'] ?? null) ? $row['user_agent'] : null,
            occurredAt: new DateTimeImmutable($occurredAtStr, new DateTimeZone('UTC'))
        );

        return new BehaviorTraceEventDTO(
            eventId: $eventId,
            action: $action,
            entityType: is_string($row['entity_type'] ?? null) ? $row['entity_type'] : null,
            entityId: is_int($row['entity_id'] ?? null) ? $row['entity_id'] : null,
            context: $context,
            metadata: $metadata
        );
    }
}
