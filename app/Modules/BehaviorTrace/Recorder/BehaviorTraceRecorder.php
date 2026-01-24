<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Recorder;

use Maatify\BehaviorTrace\Contract\BehaviorTracePolicyInterface;
use Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface;
use Maatify\BehaviorTrace\DTO\BehaviorTraceContextDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceEventDTO;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeInterface;
use Maatify\BehaviorTrace\Services\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use JsonException;
use Throwable;

class BehaviorTraceRecorder
{
    private readonly BehaviorTracePolicyInterface $policy;

    public function __construct(
        private readonly BehaviorTraceWriterInterface $writer,
        private readonly ClockInterface $clock,
        private readonly ?LoggerInterface $fallbackLogger = null,
        ?BehaviorTracePolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new BehaviorTraceDefaultPolicy();
    }

    /**
     * @param string $action
     * @param BehaviorTraceActorTypeInterface|string $actorType
     * @param int|null $actorId
     * @param string|null $entityType
     * @param int|null $entityId
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param array<mixed>|null $metadata
     */
    public function record(
        string $action,
        BehaviorTraceActorTypeInterface|string $actorType,
        ?int $actorId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): void {
        // Enforce DB Constraints (Fail-open/Truncate)
        $action = $this->truncateString($action, 128);
        $entityType = $this->truncate($entityType, 64);
        $correlationId = $this->truncate($correlationId, 36);
        $requestId = $this->truncate($requestId, 64);
        $routeName = $this->truncate($routeName, 255);
        $ipAddress = $this->truncate($ipAddress, 45);
        $userAgent = $this->truncate($userAgent, 512);

        // Normalize Actor Type via Policy
        $normalizedActorType = $this->policy->normalizeActorType($actorType);

        // Validate Metadata Size and Encoding
        if ($metadata !== null) {
            try {
                $json = json_encode($metadata, JSON_THROW_ON_ERROR);
                if (!$this->policy->validateMetadataSize($json)) {
                    if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning('Behavior trace metadata exceeded limit. Dropping metadata.', [
                            'action' => $action,
                            'size' => strlen($json)
                        ]);
                    }
                    $metadata = ['error' => 'Metadata dropped due to size limit'];
                }
            } catch (JsonException $e) {
                 if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning('Behavior trace metadata JSON encoding failed.', [
                            'action' => $action,
                            'error' => $e->getMessage()
                        ]);
                    }
                 $metadata = ['error' => 'Metadata dropped due to encoding error'];
            }
        }

        // Construct Context DTO
        $context = new BehaviorTraceContextDTO(
            actorType: $normalizedActorType,
            actorId: $actorId,
            correlationId: $correlationId,
            requestId: $requestId,
            routeName: $routeName,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            occurredAt: $this->clock->now()
        );

        // Construct Event DTO
        $dto = new BehaviorTraceEventDTO(
            eventId: Uuid::uuid4()->toString(),
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            context: $context,
            metadata: $metadata
        );

        try {
            $this->writer->write($dto);
        } catch (Throwable $e) {
            // Best-effort: swallow exception but log to fallback
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error('Behavior trace logging failed', [
                    'exception' => $e->getMessage(),
                    'action' => $action,
                ]);
            }
        }
    }

    private function truncate(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }
        return $this->truncateString($value, $limit);
    }

    private function truncateString(string $value, int $limit): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') > $limit) {
                return mb_substr($value, 0, $limit, 'UTF-8');
            }
            return $value;
        }

        if (strlen($value) > $limit) {
            return substr($value, 0, $limit);
        }
        return $value;
    }
}
