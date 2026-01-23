<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Recorder;

use JsonException;
use Maatify\BehaviorTrace\Contract\BehaviorTraceLoggerInterface;
use Maatify\BehaviorTrace\Contract\BehaviorTracePolicyInterface;
use Maatify\BehaviorTrace\Contract\ClockInterface;
use Maatify\BehaviorTrace\DTO\BehaviorTraceContextDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceRecordDTO;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;
use Maatify\BehaviorTrace\Exception\BehaviorTraceStorageException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

class BehaviorTraceRecorder
{
    private readonly BehaviorTracePolicyInterface $policy;

    public function __construct(
        private readonly BehaviorTraceLoggerInterface $writer,
        private readonly ClockInterface $clock,
        private readonly ?LoggerInterface $fallbackLogger = null,
        ?BehaviorTracePolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new BehaviorTraceDefaultPolicy();
    }

    /**
     * @param string $action
     * @param string $resource
     * @param string|null $resourceId
     * @param array<mixed>|null $payload
     * @param string|BehaviorTraceActorTypeEnum $actorType
     * @param int|null $actorId
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     */
    public function record(
        string $action,
        string $resource,
        ?string $resourceId,
        ?array $payload,
        string|BehaviorTraceActorTypeEnum $actorType,
        ?int $actorId = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            // Truncate strings to safe limits
            $action = $this->truncateString($action, 255);
            $resource = $this->truncateString($resource, 255);
            $resourceId = $this->truncate($resourceId, 255);
            $correlationId = $this->truncate($correlationId, 36);
            $requestId = $this->truncate($requestId, 64);
            $routeName = $this->truncate($routeName, 255);
            $ipAddress = $this->truncate($ipAddress, 45);
            $userAgent = $this->truncate($userAgent, 512);

            // Normalize Actor Type
            $normalizedActorType = $this->policy->normalizeActorType($actorType);

            // Validate Payload
            if ($payload !== null) {
                try {
                    $json = json_encode($payload, JSON_THROW_ON_ERROR);
                    if (!$this->policy->validatePayloadSize($json)) {
                        if ($this->fallbackLogger) {
                            $this->fallbackLogger->warning('BehaviorTrace payload exceeded limit. Dropping payload.', [
                                'action' => $action,
                                'resource' => $resource,
                                'size' => strlen($json)
                            ]);
                        }
                        $payload = ['error' => 'Payload dropped due to size limit'];
                    }
                } catch (JsonException $e) {
                    if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning('BehaviorTrace payload JSON encoding failed.', [
                            'action' => $action,
                            'resource' => $resource,
                            'error' => $e->getMessage()
                        ]);
                    }
                    $payload = ['error' => 'Payload dropped due to encoding error'];
                }
            }

            // Construct Context
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

            // Construct Record DTO
            $dto = new BehaviorTraceRecordDTO(
                eventId: Uuid::uuid4()->toString(),
                action: $action,
                resource: $resource,
                resourceId: $resourceId,
                payload: $payload,
                context: $context
            );

            // Write
            $this->writer->write($dto);

        } catch (BehaviorTraceStorageException $e) {
            // Best-effort: swallow storage exception
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error('BehaviorTrace logging failed (Storage)', [
                    'exception' => $e->getMessage(),
                    'action' => $action ?? 'unknown',
                ]);
            }
        } catch (Throwable $e) {
            // Best-effort: catch all other exceptions (e.g. UUID generation, DTO construction)
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error('BehaviorTrace logging failed (Unexpected)', [
                    'exception' => $e->getMessage(),
                    'action' => $action ?? 'unknown',
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
