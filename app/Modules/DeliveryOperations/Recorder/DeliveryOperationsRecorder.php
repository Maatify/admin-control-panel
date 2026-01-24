<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Recorder;

use BackedEnum;
use UnitEnum;
use DateTimeImmutable;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsPolicyInterface;
use Maatify\DeliveryOperations\DTO\DeliveryOperationRecordDTO;
use Maatify\DeliveryOperations\Enum\DeliveryActorTypeInterface;
use Maatify\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\DeliveryOperations\Enum\DeliveryStatusEnum;
use Maatify\DeliveryOperations\Services\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;
use JsonException;

class DeliveryOperationsRecorder
{
    private readonly DeliveryOperationsPolicyInterface $policy;

    public function __construct(
        private readonly DeliveryOperationsLoggerInterface $writer,
        private readonly ClockInterface $clock,
        private readonly ?LoggerInterface $fallbackLogger = null,
        ?DeliveryOperationsPolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new DeliveryOperationsDefaultPolicy();
    }

    /**
     * @param DeliveryChannelEnum|string $channel
     * @param DeliveryOperationTypeEnum|string $operationType
     * @param DeliveryStatusEnum|string $status
     * @param int $attemptNo
     * @param DeliveryActorTypeInterface|string|null $actorType
     * @param int|null $actorId
     * @param string|null $targetType
     * @param int|null $targetId
     * @param DateTimeImmutable|null $scheduledAt
     * @param DateTimeImmutable|null $completedAt
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $provider
     * @param string|null $providerMessageId
     * @param string|null $errorCode
     * @param string|null $errorMessage
     * @param array<mixed>|null $metadata
     */
    public function record(
        DeliveryChannelEnum|string $channel,
        DeliveryOperationTypeEnum|string $operationType,
        DeliveryStatusEnum|string $status,
        int $attemptNo = 0,
        DeliveryActorTypeInterface|string|null $actorType = null,
        ?int $actorId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?DateTimeImmutable $scheduledAt = null,
        ?DateTimeImmutable $completedAt = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $provider = null,
        ?string $providerMessageId = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): void {
        try {
            // Normalize Enums
            $channelStr = $this->enumToString($channel);
            $operationTypeStr = $this->enumToString($operationType);
            $statusStr = $this->enumToString($status);

            // Normalize Actor Type
            $normalizedActorType = null;
            if ($actorType !== null) {
                $normalizedActorType = $this->policy->normalizeActorType($actorType);
            }

            // Truncate fields
            $channelStr = $this->truncateString($channelStr, 32);
            $operationTypeStr = $this->truncateString($operationTypeStr, 64);
            $statusStr = $this->truncateString($statusStr, 32);
            $targetType = $this->truncate($targetType, 64);
            $correlationId = $this->truncate($correlationId, 36);
            $requestId = $this->truncate($requestId, 64);
            $provider = $this->truncate($provider, 64);
            $providerMessageId = $this->truncate($providerMessageId, 128);
            $errorCode = $this->truncate($errorCode, 64);
            // errorMessage is TEXT, usually big enough, but let's be safe if we want strictly robust? No limit defined in schema (TEXT = 64KB)
            // But let's not aggressively truncate TEXT unless needed.

            // Validate Metadata
            if ($metadata !== null) {
                try {
                    $json = json_encode($metadata, JSON_THROW_ON_ERROR);
                    if (!$this->policy->validateMetadataSize($json)) {
                        if ($this->fallbackLogger) {
                            $this->fallbackLogger->warning('DeliveryOperations metadata too large', ['size' => strlen($json)]);
                        }
                        $metadata = ['error' => 'Metadata dropped: too large'];
                    }
                } catch (JsonException $e) {
                     if ($this->fallbackLogger) {
                            $this->fallbackLogger->warning('DeliveryOperations metadata encoding failed', ['error' => $e->getMessage()]);
                        }
                     $metadata = ['error' => 'Metadata dropped: encoding error'];
                }
            } else {
                $metadata = []; // Ensure not null for DTO/DB if needed, though DTO allows null but DB is JSON NOT NULL. Logic in Repo handles null->[]
            }

            $dto = new DeliveryOperationRecordDTO(
                eventId: Uuid::uuid4()->toString(),
                channel: $channelStr,
                operationType: $operationTypeStr,
                actorType: $normalizedActorType,
                actorId: $actorId,
                targetType: $targetType,
                targetId: $targetId,
                status: $statusStr,
                attemptNo: $attemptNo,
                scheduledAt: $scheduledAt,
                completedAt: $completedAt,
                correlationId: $correlationId,
                requestId: $requestId,
                provider: $provider,
                providerMessageId: $providerMessageId,
                errorCode: $errorCode,
                errorMessage: $errorMessage,
                metadata: $metadata,
                occurredAt: $this->clock->now()
            );

            $this->writer->log($dto);

        } catch (Throwable $e) {
            // Fail-open: swallow exception
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error('DeliveryOperations logging failed', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    private function enumToString(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }
        if ($value instanceof UnitEnum) {
            return $value->name;
        }
        if (is_object($value) && method_exists($value, 'value')) {
            /** @var mixed $val */
            $val = $value->value();
            if (is_string($val) || is_int($val)) {
                return (string) $val;
            }
        }

        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        return '';
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
        if (mb_strlen($value) > $limit) {
            return mb_substr($value, 0, $limit);
        }
        return $value;
    }
}
