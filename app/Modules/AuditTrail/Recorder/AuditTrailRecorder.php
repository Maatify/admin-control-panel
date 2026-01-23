<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Recorder;

use Maatify\AuditTrail\Contract\AuditTrailLoggerInterface;
use Maatify\AuditTrail\Contract\AuditTrailPolicyInterface;
use Maatify\AuditTrail\DTO\AuditTrailRecordDTO;
use Maatify\AuditTrail\Enum\AuditTrailActorTypeEnum;
use Maatify\AuditTrail\Services\ClockInterface;
use Ramsey\Uuid\Uuid;
use Throwable;
use JsonException;

class AuditTrailRecorder
{
    private readonly AuditTrailPolicyInterface $policy;

    public function __construct(
        private readonly AuditTrailLoggerInterface $logger,
        private readonly ClockInterface $clock,
        ?AuditTrailPolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new AuditTrailDefaultPolicy();
    }

    /**
     * @param string $eventKey
     * @param string|AuditTrailActorTypeEnum $actorType
     * @param int|null $actorId
     * @param string $entityType
     * @param int|null $entityId
     * @param string|null $subjectType
     * @param int|null $subjectId
     * @param array<string, mixed>|null $metadata
     * @param string|null $referrerRouteName
     * @param string|null $referrerPath
     * @param string|null $referrerHost
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     */
    public function record(
        string $eventKey,
        string|AuditTrailActorTypeEnum $actorType,
        ?int $actorId,
        string $entityType,
        ?int $entityId,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null,
        ?string $referrerRouteName = null,
        ?string $referrerPath = null,
        ?string $referrerHost = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            // 0. Sanitize Referrer Path (NO query strings allowed)
            if ($referrerPath !== null) {
                $parsed = parse_url($referrerPath, PHP_URL_PATH);
                if (is_string($parsed)) {
                    $referrerPath = $parsed;
                } else {
                    $referrerPath = explode('?', $referrerPath)[0];
                }
            }

            // 1. Policy Normalization
            $normalizedActorType = $this->policy->normalizeActorType($actorType);

            // 2. Validate Metadata
            if ($metadata !== null) {
                try {
                    $json = json_encode($metadata, JSON_THROW_ON_ERROR);
                    if (!$this->policy->validateMetadataSize($json)) {
                        error_log('[AuditTrail] Metadata exceeded limit. Dropping metadata for event: ' . $eventKey);
                        $metadata = ['error' => 'Metadata dropped due to size limit'];
                    }
                } catch (JsonException $e) {
                    error_log('[AuditTrail] Metadata encoding failed for event: ' . $eventKey . '. Error: ' . $e->getMessage());
                    $metadata = ['error' => 'Metadata dropped due to encoding error'];
                }
            } else {
                $metadata = [];
            }

            // 3. Construct DTO
            $recordDTO = new AuditTrailRecordDTO(
                eventId: Uuid::uuid4()->toString(),
                actorType: $normalizedActorType,
                actorId: $actorId,
                eventKey: $eventKey,
                entityType: $entityType,
                entityId: $entityId,
                subjectType: $subjectType,
                subjectId: $subjectId,
                referrerRouteName: $referrerRouteName,
                referrerPath: $referrerPath,
                referrerHost: $referrerHost,
                correlationId: $correlationId,
                requestId: $requestId,
                routeName: $routeName,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                metadata: $metadata,
                occurredAt: $this->clock->now()
            );

            // 4. Persist
            $this->logger->write($recordDTO);

        } catch (Throwable $e) {
            // 5. Fail-Open: Swallow and log to fallback (primitive error_log)
            error_log(sprintf(
                '[AuditTrail] Recording failed for event %s: %s',
                $eventKey,
                $e->getMessage()
            ));
        }
    }
}
