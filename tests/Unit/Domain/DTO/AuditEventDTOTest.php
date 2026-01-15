<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\DTO;

use App\Domain\DTO\AuditEventDTO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class AuditEventDTOTest extends TestCase
{
    /**
     * Test request_id enforcement.
     */
    public function test_it_includes_request_id_in_serialization(): void
    {
        $dto = new AuditEventDTO(
            1,
            'action',
            'target',
            123,
            'low',
            [],
            'correlation_id',
            'req_123',
            new DateTimeImmutable('2023-01-01 00:00:00')
        );

        $serialized = $dto->jsonSerialize();

        $this->assertArrayHasKey('request_id', $serialized);
        $this->assertSame('req_123', $serialized['request_id']);
    }

    public function test_it_fails_on_missing_request_id(): void
    {
        $this->expectException(\ArgumentCountError::class);

        // @phpstan-ignore-next-line
        new AuditEventDTO(
            actor_id: 1,
            action: 'action',
            target_type: 'target',
            target_id: 123,
            risk_level: 'low',
            payload:[],
            correlation_id: 'correlation_id',
            // Missing request_id
            created_at: new DateTimeImmutable()
        );
    }
}
