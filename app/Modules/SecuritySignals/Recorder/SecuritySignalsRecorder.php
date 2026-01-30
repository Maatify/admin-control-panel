<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Recorder;

use Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface;
use Maatify\SecuritySignals\Contract\SecuritySignalsPolicyInterface;
use Maatify\SecuritySignals\DTO\SecuritySignalRecordDTO;
use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;
use Maatify\SecuritySignals\Enum\SecuritySignalSeverityEnum;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use JsonException;
use Throwable;

class SecuritySignalsRecorder
{
    private readonly SecuritySignalsPolicyInterface $policy;

    public function __construct(
        private readonly SecuritySignalsLoggerInterface $logger,
        private readonly ClockInterface $clock,
        private readonly ?LoggerInterface $fallbackLogger = null,
        ?SecuritySignalsPolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new SecuritySignalsDefaultPolicy();
    }

    /**
     * @param string $signalType
     * @param string|SecuritySignalSeverityEnum $severity
     * @param string|SecuritySignalActorTypeEnum $actorType
     * @param int|null $actorId
     * @param array<string, mixed>|null $metadata
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     */
    public function record(
        string $signalType,
        string|SecuritySignalSeverityEnum $severity,
        string|SecuritySignalActorTypeEnum $actorType,
        ?int $actorId,
        ?array $metadata = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            // 1. Normalize inputs via policy
            $normalizedActorType = $this->policy->normalizeActorType($actorType);
            $normalizedSeverity = $this->policy->normalizeSeverity($severity);

            // 2. Sanitize strings to DB limits
            $safeSignalType = substr($signalType, 0, 100);
            $safeActorType = substr($normalizedActorType, 0, 32);
            $safeSeverity = substr($normalizedSeverity, 0, 16);
            $safeCorrelationId = $correlationId ? substr($correlationId, 0, 36) : null;
            $safeRequestId = $requestId ? substr($requestId, 0, 64) : null;
            $safeRouteName = $routeName ? substr($routeName, 0, 255) : null;
            $safeIpAddress = $ipAddress ? substr($ipAddress, 0, 45) : null;
            $safeUserAgent = $userAgent ? substr($userAgent, 0, 512) : null;

            // 3. Handle Metadata
            if ($metadata !== null) {
                try {
                    $json = json_encode($metadata, JSON_THROW_ON_ERROR);
                    if (!$this->policy->validateMetadataSize($json)) {
                        if ($this->fallbackLogger) {
                            $this->fallbackLogger->warning(
                                'SecuritySignals metadata exceeded limit. Dropping metadata.',
                                [
                                    'signal_type' => $safeSignalType,
                                    'size' => strlen($json),
                                ]
                            );
                        }
                        $metadata = ['error' => 'Metadata dropped due to size limit'];
                    }
                } catch (JsonException $e) {
                    if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning(
                            'SecuritySignals metadata JSON encoding failed.',
                            [
                                'signal_type' => $safeSignalType,
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                    $metadata = ['error' => 'Metadata dropped due to encoding error'];
                }
            } else {
                $metadata = [];
            }

            // 4. Construct DTO
            $recordDTO = new SecuritySignalRecordDTO(
                eventId: Uuid::uuid4()->toString(),
                actorType: $safeActorType,
                actorId: $actorId,
                signalType: $safeSignalType,
                severity: $safeSeverity,
                correlationId: $safeCorrelationId,
                requestId: $safeRequestId,
                routeName: $safeRouteName,
                ipAddress: $safeIpAddress,
                userAgent: $safeUserAgent,
                metadata: $metadata,
                occurredAt: $this->clock->now()
            );

            // 5. Persist
            $this->logger->write($recordDTO);

        } catch (Throwable $e) {
            // Fail-open: swallow exception
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error(
                    'SecuritySignals logging failed',
                    [
                        'signal_type' => substr($signalType, 0, 100), // best effort log
                        'exception' => $e->getMessage(),
                    ]
                );
            }
        }
    }
}
