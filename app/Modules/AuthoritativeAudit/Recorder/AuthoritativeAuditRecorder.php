<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Recorder;

use BackedEnum;
use Maatify\SharedCommon\Contracts\ClockInterface;
use UnitEnum;
use Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditOutboxWriterInterface;
use Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditPolicyInterface;
use Maatify\AuthoritativeAudit\DTO\AuthoritativeAuditOutboxWriteDTO;
use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditActorTypeInterface;
use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditRiskLevelEnum;
use Maatify\AuthoritativeAudit\Exception\AuthoritativeAuditStorageException;
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
     * @param string $action
     * @param string $targetType
     * @param int|null $targetId
     * @param AuthoritativeAuditRiskLevelEnum|string $riskLevel
     * @param AuthoritativeAuditActorTypeInterface|string $actorType
     * @param int|null $actorId
     * @param array<mixed> $payload
     * @param string $correlationId
     * @throws AuthoritativeAuditStorageException
     * @throws InvalidArgumentException
     */
    public function record(
        string $action,
        string $targetType,
        ?int $targetId,
        AuthoritativeAuditRiskLevelEnum|string $riskLevel,
        AuthoritativeAuditActorTypeInterface|string $actorType,
        ?int $actorId,
        array $payload,
        string $correlationId
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
        $action = $this->truncateString($action, 128);
        $targetType = $this->truncateString($targetType, 64);
        $correlationId = $this->truncateString($correlationId, 36);

        $dto = new AuthoritativeAuditOutboxWriteDTO(
            eventId: Uuid::uuid4()->toString(),
            actorType: $normalizedActorType,
            actorId: $actorId,
            action: $action,
            targetType: $targetType,
            targetId: $targetId,
            riskLevel: $riskLevelStr,
            payload: $payload,
            correlationId: $correlationId,
            createdAt: $this->clock->now()
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

    private function truncateString(string $value, int $limit): string
    {
        if (mb_strlen($value) > $limit) {
            return mb_substr($value, 0, $limit);
        }
        return $value;
    }
}
