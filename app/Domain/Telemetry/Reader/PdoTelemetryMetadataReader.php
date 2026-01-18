<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 02:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\Reader;

use App\Domain\Telemetry\Contracts\TelemetryMetadataReaderInterface;
use App\Domain\Telemetry\DTO\TelemetryMetadataViewDTO;
use PDO;
use RuntimeException;

final readonly class PdoTelemetryMetadataReader implements TelemetryMetadataReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getById(int $id): TelemetryMetadataViewDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                id,
                event_key,
                severity,
                route_name,
                request_id,
                actor_type,
                actor_id,
                ip_address,
                user_agent,
                metadata,
                occurred_at
             FROM telemetry_traces
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new RuntimeException('Telemetry trace not found');
        }

        // ─────────────────────────────
        // Required fields (fail-closed)
        // ─────────────────────────────

        $idRaw = $row['id'] ?? null;
        if (!is_numeric($idRaw)) {
            throw new RuntimeException('Invalid telemetry id value');
        }
        $telemetryId = (int) $idRaw;

        $eventKeyRaw = $row['event_key'] ?? null;
        if (!is_string($eventKeyRaw) || $eventKeyRaw === '') {
            throw new RuntimeException('Invalid telemetry event_key value');
        }
        $eventKey = $eventKeyRaw;

        $severityRaw = $row['severity'] ?? null;
        if (!is_string($severityRaw) || $severityRaw === '') {
            throw new RuntimeException('Invalid telemetry severity value');
        }
        $severity = $severityRaw;

        $occurredAtRaw = $row['occurred_at'] ?? null;
        if (!is_string($occurredAtRaw) || $occurredAtRaw === '') {
            throw new RuntimeException('Invalid telemetry occurred_at value');
        }
        $occurredAt = $occurredAtRaw;

        $actorTypeRaw = $row['actor_type'] ?? null;
        if (!is_string($actorTypeRaw) || $actorTypeRaw === '') {
            throw new RuntimeException('Invalid telemetry actor_type value');
        }
        $actorType = $actorTypeRaw;

        // ─────────────────────────────
        // Nullable scalar fields
        // ─────────────────────────────

        $routeName = null;
        $routeNameRaw = $row['route_name'] ?? null;
        if ($routeNameRaw !== null) {
            if (!is_string($routeNameRaw)) {
                throw new RuntimeException('Invalid telemetry route_name value');
            }
            $routeName = $routeNameRaw;
        }

        $requestId = null;
        $requestIdRaw = $row['request_id'] ?? null;
        if ($requestIdRaw !== null) {
            if (!is_string($requestIdRaw)) {
                throw new RuntimeException('Invalid telemetry request_id value');
            }
            $requestId = $requestIdRaw;
        }

        $ipAddress = null;
        $ipAddressRaw = $row['ip_address'] ?? null;
        if ($ipAddressRaw !== null) {
            if (!is_string($ipAddressRaw)) {
                throw new RuntimeException('Invalid telemetry ip_address value');
            }
            $ipAddress = $ipAddressRaw;
        }

        $userAgent = null;
        $userAgentRaw = $row['user_agent'] ?? null;
        if ($userAgentRaw !== null) {
            if (!is_string($userAgentRaw)) {
                throw new RuntimeException('Invalid telemetry user_agent value');
            }
            $userAgent = $userAgentRaw;
        }

        $actorId = null;
        $actorIdRaw = $row['actor_id'] ?? null;
        if ($actorIdRaw !== null) {
            if (!is_numeric($actorIdRaw)) {
                throw new RuntimeException('Invalid telemetry actor_id value');
            }
            $actorId = (int) $actorIdRaw;
        }

        // ─────────────────────────────
        // Metadata JSON (nullable)
        // ─────────────────────────────

        $metadata = [];
        $metadataRaw = $row['metadata'] ?? null;

        if ($metadataRaw !== null) {
            if (!is_string($metadataRaw)) {
                throw new RuntimeException('Invalid telemetry metadata value');
            }

            $decoded = json_decode($metadataRaw, true);

            if (is_array($decoded)) {
                $metadata = $decoded;
            } else {
                // If metadata exists but is not valid JSON, fail-closed (strict)
                throw new RuntimeException('Telemetry metadata is not valid JSON');
            }
        }

        return new TelemetryMetadataViewDTO(
            id: $telemetryId,
            eventKey: $eventKey,
            severity: $severity,
            routeName: $routeName,
            requestId: $requestId,
            actorType: $actorType,
            actorId: $actorId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: $metadata,
            occurredAt: $occurredAt
        );
    }
}
