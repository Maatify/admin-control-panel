<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Recorder;

use BackedEnum;
use UnitEnum;
use Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface;
use Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditPolicyInterface;
use Maatify\AuthoritativeAudit\DTO\AuthoritativeAuditContextDTO;
use Maatify\AuthoritativeAudit\DTO\AuthoritativeAuditOutboxWriteDTO;
use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditActorTypeInterface;
use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditRiskLevelEnum;
use Maatify\AuthoritativeAudit\Exception\AuthoritativeAuditStorageException;
use Maatify\AuthoritativeAudit\Services\ClockInterface;
use Ramsey\Uuid\Uuid;
use InvalidArgumentException;

class AuthoritativeAuditRecorder
{
    private readonly AuthoritativeAuditPolicyInterface $policy;

    public function __construct(
        private readonly AuthoritativeAuditOutboxWriterInterface $writer,
        private readonly ClockInterface $clock,
        ?AuthoritativeAuditPolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new AuthoritativeAuditDefaultPolicy();
    }

    /**
     * @param string $eventKey
     * @param AuthoritativeAuditRiskLevelEnum|string $riskLevel
     * @param AuthoritativeAuditActorTypeInterface|string $actorType
     * @param array<mixed> $payload
     * @param int|null $actorId
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @throws AuthoritativeAuditStorageException
     * @throws InvalidArgumentException
     */
    public function record(
        string $eventKey,
        AuthoritativeAuditRiskLevelEnum|string $riskLevel,
        AuthoritativeAuditActorTypeInterface|string $actorType,
        array $payload,
        ?int $actorId = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        // No Try-Catch block here (Fail-Closed)

        // Validate Payload
        if (!$this->policy->validatePayload($payload)) {
            throw new InvalidArgumentException('AuthoritativeAudit payload validation failed: Secrets detected or invalid content.');
        }

        // Normalize Enums
        $riskLevelStr = $this->enumToString($riskLevel);

        // Normalize Actor Type
        $normalizedActorType = $this->policy->normalizeActorType($actorType);

        // Truncate strings (DB safety)
        $eventKey = $this->truncateString($eventKey, 255);
        $correlationId = $this->truncate($correlationId, 36);
        $requestId = $this->truncate($requestId, 64);
        $routeName = $this->truncate($routeName, 255);
        $ipAddress = $this->truncate($ipAddress, 45);
        $userAgent = $this->truncate($userAgent, 512);

        $context = new AuthoritativeAuditContextDTO(
            actorType: $normalizedActorType,
            actorId: $actorId,
            correlationId: $correlationId,
            requestId: $requestId,
            routeName: $routeName,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            occurredAt: $this->clock->now()
        );

        $dto = new AuthoritativeAuditOutboxWriteDTO(
            eventId: Uuid::uuid4()->toString(),
            eventKey: $eventKey,
            riskLevel: $riskLevelStr,
            context: $context,
            payload: $payload
        );

        $this->writer->write($dto);
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
