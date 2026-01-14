<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\DTO;

use App\Domain\DTO\AuditEventDTO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class AuditEventDTOTest extends TestCase
{
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
            1,
            'action',
            'target',
            123,
            'low',
            [],
            'correlation_id',
            // Missing request_id
            new DateTimeImmutable()
        );
    }
}
