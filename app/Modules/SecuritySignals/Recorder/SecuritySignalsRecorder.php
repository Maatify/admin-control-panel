<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Recorder;

use Maatify\SecuritySignals\Contract\SecuritySignalsLoggerInterface;
use Maatify\SecuritySignals\Contract\SecuritySignalsPolicyInterface;
use Maatify\SecuritySignals\DTO\SecuritySignalRecordDTO;
use Maatify\SecuritySignals\Enum\SecuritySignalActorTypeEnum;
use Maatify\SecuritySignals\Enum\SecuritySignalSeverityEnum;
use Maatify\SecuritySignals\Exception\SecuritySignalsStorageException;
use Maatify\SecuritySignals\Services\ClockInterface;
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
        // 1. Normalize actor type via policy
        $normalizedActorType = $this->policy->normalizeActorType($actorType);

        // 2. Normalize severity
        $normalizedSeverity = $severity instanceof SecuritySignalSeverityEnum ? $severity->value : $severity;

        // 3. Validate metadata size & encoding
        if ($metadata !== null) {
            try {
                $json = json_encode($metadata, JSON_THROW_ON_ERROR);
                if (!$this->policy->validateMetadataSize($json)) {
                    if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning(
                            'SecuritySignals metadata exceeded limit. Dropping metadata.',
                            [
                                'signal_type' => $signalType,
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
                            'signal_type' => $signalType,
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
            actorType: $normalizedActorType,
            actorId: $actorId,
            signalType: $signalType,
            severity: $normalizedSeverity,
            correlationId: $correlationId,
            requestId: $requestId,
            routeName: $routeName,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: $metadata,
            occurredAt: $this->clock->now()
        );

        // 5. Persist (Fail-Open on storage only)
        try {
            $this->logger->write($recordDTO);
        } catch (Throwable $e) {
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error(
                    'SecuritySignals logging failed',
                    [
                        'signal_type' => $signalType,
                        'exception' => $e->getMessage(),
                    ]
                );
            }
        }
    }
}
