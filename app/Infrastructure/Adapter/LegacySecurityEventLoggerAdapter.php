<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;

final class LegacySecurityEventLoggerAdapter implements SecurityEventLoggerInterface
{
    public function __construct(
        private readonly SecurityEventRecorderInterface $recorder
    ) {
    }

    public function log(SecurityEventDTO $event): void
    {
        // 1. Map Actor
        if ($event->adminId !== null) {
            $actorType = SecurityEventActorTypeEnum::ADMIN;
            $actorId = (int) $event->adminId;
        } else {
            $actorType = SecurityEventActorTypeEnum::ANONYMOUS;
            $actorId = null;
        }

        // 2. Map Event Type
        $eventName = $event->eventName;
        $eventType = match ($eventName) {
            'admin_logout' => SecurityEventTypeEnum::LOGOUT,
            'login_failed', 'login_blocked' => SecurityEventTypeEnum::LOGIN_FAILED,
            'permission_denied', 'recovery_action_blocked' => SecurityEventTypeEnum::PERMISSION_DENIED,
            'session_validation_failed' => SecurityEventTypeEnum::SESSION_INVALID,
            default => SecurityEventTypeEnum::LEGACY_UNMAPPED,
        };

        // 3. Map Severity
        $severity = match ($event->severity) {
            'critical' => SecurityEventSeverityEnum::CRITICAL,
            'warning' => SecurityEventSeverityEnum::WARNING,
            'error' => SecurityEventSeverityEnum::ERROR,
            'info' => SecurityEventSeverityEnum::INFO,
            default => SecurityEventSeverityEnum::INFO,
        };

        // 4. Extract Context
        $context = $event->context;

        /** @var string|null $requestId */
        $requestId = $context['request_id'] ?? null;
        if (!is_string($requestId)) {
            $requestId = null;
        }

        /** @var string|null $routeName */
        $routeName = $context['route_name'] ?? null;
        if (!is_string($routeName)) {
            $routeName = null;
        }

        // 5. Metadata
        $metadata = $context;
        $metadata['legacy_event_name'] = $event->eventName;
        $metadata['legacy_severity'] = $event->severity;

        // 6. Construct DTO
        $recordDto = new SecurityEventRecordDTO(
            actorType: $actorType,
            actorId: $actorId,
            eventType: $eventType,
            severity: $severity,
            requestId: $requestId,
            routeName: $routeName,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
            metadata: $metadata
        );

        $this->recorder->record($recordDto);
    }
}
