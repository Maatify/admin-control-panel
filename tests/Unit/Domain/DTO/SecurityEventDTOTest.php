<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\DTO;

use App\Domain\DTO\SecurityEventDTO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SecurityEventDTOTest extends TestCase
{
    public function test_it_does_not_inject_request_id_into_context(): void
    {
        $dto = new SecurityEventDTO(
            1,
            'test_event',
            'low',
            ['foo' => 'bar'],
            '127.0.0.1',
            'UserAgent',
            new DateTimeImmutable(),
            'req_123'
        );

        $this->assertArrayNotHasKey('request_id', $dto->context);
    }

    public function test_it_preserves_other_context_fields(): void
    {
        $dto = new SecurityEventDTO(
            1,
            'test_event',
            'low',
            ['foo' => 'bar', 'baz' => 123],
            '127.0.0.1',
            'UserAgent',
            new DateTimeImmutable(),
            'req_123'
        );

        $this->assertSame('bar', $dto->context['foo']);
        $this->assertSame(123, $dto->context['baz']);
        $this->assertArrayNotHasKey('request_id', $dto->context);
    }
}
