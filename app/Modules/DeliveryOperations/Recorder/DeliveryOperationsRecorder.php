<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Recorder;

use BackedEnum;
use UnitEnum;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsPolicyInterface;
use Maatify\DeliveryOperations\DTO\DeliveryOperationContextDTO;
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
     * @param DeliveryOperationTypeEnum|string $operationType
     * @param DeliveryChannelEnum|string $channel
     * @param DeliveryStatusEnum|string $status
     * @param int $attemptCount
     * @param string|null $providerId
     * @param DeliveryActorTypeInterface|string $actorType
     * @param int|null $actorId
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param array<mixed>|null $metadata
     */
    public function record(
        DeliveryOperationTypeEnum|string $operationType,
        DeliveryChannelEnum|string $channel,
        DeliveryStatusEnum|string $status,
        int $attemptCount,
        ?string $providerId,
        DeliveryActorTypeInterface|string $actorType,
        ?int $actorId = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): void {
        try {
            // Normalize Enums
            $operationTypeStr = $this->enumToString($operationType);
            $channelStr = $this->enumToString($channel);
            $statusStr = $this->enumToString($status);

            // Truncate fields
            $operationTypeStr = $this->truncateString($operationTypeStr, 64);
            $channelStr = $this->truncateString($channelStr, 32);
            $statusStr = $this->truncateString($statusStr, 32);
            $providerId = $this->truncate($providerId, 128);
            $correlationId = $this->truncate($correlationId, 36);
            $requestId = $this->truncate($requestId, 64);
            $routeName = $this->truncate($routeName, 255);
            $ipAddress = $this->truncate($ipAddress, 45);
            $userAgent = $this->truncate($userAgent, 512);

            // Normalize Actor Type
            $normalizedActorType = $this->policy->normalizeActorType($actorType);

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
            }

            $context = new DeliveryOperationContextDTO(
                actorType: $normalizedActorType,
                actorId: $actorId,
                correlationId: $correlationId,
                requestId: $requestId,
                routeName: $routeName,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $this->clock->now()
            );

            $dto = new DeliveryOperationRecordDTO(
                eventId: Uuid::uuid4()->toString(),
                operationType: $operationTypeStr,
                channel: $channelStr,
                status: $statusStr,
                attemptCount: $attemptCount,
                providerId: $providerId,
                context: $context,
                metadata: $metadata
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
