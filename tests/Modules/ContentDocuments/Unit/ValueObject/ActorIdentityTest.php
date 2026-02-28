<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\ValueObject;

use Maatify\ContentDocuments\Domain\Exception\InvalidActorIdentityException;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use PHPUnit\Framework\TestCase;

final class ActorIdentityTest extends TestCase
{
    public function testValidActorIdentity(): void
    {
        $a = new ActorIdentity('user', 123);
        self::assertSame('user', $a->actorType());
        self::assertSame(123, $a->actorId());
        self::assertSame('user:123', (string)$a);
    }

    public function testRejectsEmptyActorType(): void
    {
        $this->expectException(InvalidActorIdentityException::class);
        new ActorIdentity('  ', 1);
    }

    public function testRejectsUppercaseActorType(): void
    {
        $this->expectException(InvalidActorIdentityException::class);
        new ActorIdentity('User', 1);
    }

    public function testRejectsUnderscoreActorType(): void
    {
        $this->expectException(InvalidActorIdentityException::class);
        new ActorIdentity('customer_admin', 1);
    }

    public function testRejectsNonPositiveActorId(): void
    {
        $this->expectException(InvalidActorIdentityException::class);
        new ActorIdentity('user', 0);
    }

    public function testEquals(): void
    {
        self::assertTrue((new ActorIdentity('admin', 10))->equals(new ActorIdentity('admin', 10)));
        self::assertFalse((new ActorIdentity('admin', 10))->equals(new ActorIdentity('admin', 11)));
        self::assertFalse((new ActorIdentity('admin', 10))->equals(new ActorIdentity('user', 10)));
    }
}
