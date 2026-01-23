<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Infrastructure\Mysql;

use DateTimeImmutable;
use DateTimeZone;
use Maatify\AuditTrail\Contract\AuditTrailQueryInterface;
use Maatify\AuditTrail\DTO\AuditTrailQueryDTO;
use Maatify\AuditTrail\DTO\AuditTrailViewDTO;
use Maatify\AuditTrail\Exception\AuditTrailStorageException;
use PDO;
use PDOException;
use Exception;

class AuditTrailQueryMysqlRepository implements AuditTrailQueryInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function find(AuditTrailQueryDTO $query): array
    {
        $conditions = [];
        $params = [];

        if ($query->actorType !== null) {
            $conditions[] = 'actor_type = :actor_type';
            $params['actor_type'] = $query->actorType;
        }

        if ($query->actorId !== null) {
            $conditions[] = 'actor_id = :actor_id';
            $params['actor_id'] = $query->actorId;
        }

        if ($query->eventKey !== null) {
            $conditions[] = 'event_key = :event_key';
            $params['event_key'] = $query->eventKey;
        }

        if ($query->correlationId !== null) {
            $conditions[] = 'correlation_id = :correlation_id';
            $params['correlation_id'] = $query->correlationId;
        }

        if ($query->after !== null) {
            $conditions[] = 'occurred_at >= :after';
            $params['after'] = $query->after->format('Y-m-d H:i:s.u');
        }

        if ($query->before !== null) {
            $conditions[] = 'occurred_at <= :before';
            $params['before'] = $query->before->format('Y-m-d H:i:s.u');
        }

        // Cursor pagination (Next Page logic for DESC order)
        if ($query->cursorOccurredAt !== null && $query->cursorId !== null) {
            $conditions[] = '(occurred_at < :cursor_at OR (occurred_at = :cursor_at AND id < :cursor_id))';
            $params['cursor_at'] = $query->cursorOccurredAt->format('Y-m-d H:i:s.u');
            $params['cursor_id'] = $query->cursorId;
        }

        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $limit = (int) $query->limit;

        $sql = <<<SQL
            SELECT *
            FROM audit_trail
            {$whereClause}
            ORDER BY occurred_at DESC, id DESC
            LIMIT {$limit}
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($rows as $row) {
                $results[] = $this->mapRowToDTO($row);
            }

            return $results;
        } catch (PDOException $e) {
            throw new AuditTrailStorageException(
                message: "Failed to query audit trail: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return AuditTrailViewDTO
     */
    private function mapRowToDTO(array $row): AuditTrailViewDTO
    {
        try {
            $occurredAtString = $row['occurred_at'] ?? 'now';
            if (!is_string($occurredAtString)) {
                $occurredAtString = 'now';
            }
            $occurredAt = new DateTimeImmutable($occurredAtString, new DateTimeZone('UTC'));
        } catch (Exception) {
            // Fallback for extremely corrupted date (should not happen in strict schema)
            $occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        }

        $metadata = null;
        if (isset($row['metadata']) && is_string($row['metadata'])) {
            try {
                $decoded = json_decode($row['metadata'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            } catch (Exception) {
                // Swallow JSON errors on read as per spec
                $metadata = null;
            }
        }

        return new AuditTrailViewDTO(
            id: $this->toInt($row['id'] ?? 0),
            eventId: $this->toString($row['event_id'] ?? ''),
            actorType: $this->toString($row['actor_type'] ?? ''),
            actorId: $this->toNullableInt($row['actor_id'] ?? null),
            eventKey: $this->toString($row['event_key'] ?? ''),
            entityType: $this->toString($row['entity_type'] ?? ''),
            entityId: $this->toNullableInt($row['entity_id'] ?? null),
            subjectType: $this->toNullableString($row['subject_type'] ?? null),
            subjectId: $this->toNullableInt($row['subject_id'] ?? null),
            referrerRouteName: $this->toNullableString($row['referrer_route_name'] ?? null),
            referrerPath: $this->toNullableString($row['referrer_path'] ?? null),
            referrerHost: $this->toNullableString($row['referrer_host'] ?? null),
            correlationId: $this->toNullableString($row['correlation_id'] ?? null),
            requestId: $this->toNullableString($row['request_id'] ?? null),
            routeName: $this->toNullableString($row['route_name'] ?? null),
            ipAddress: $this->toNullableString($row['ip_address'] ?? null),
            userAgent: $this->toNullableString($row['user_agent'] ?? null),
            metadata: $metadata,
            occurredAt: $occurredAt
        );
    }

    private function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        return '';
    }

    private function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        return 0;
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $this->toString($value);
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        return $this->toInt($value);
    }
}
