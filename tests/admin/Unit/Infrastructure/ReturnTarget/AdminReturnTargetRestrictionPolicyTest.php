<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\ReturnTarget;

use Maatify\AdminKernel\Infrastructure\ReturnTarget\AdminReturnTargetRestrictionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdminReturnTargetRestrictionPolicyTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function targets(): array
    {
        return [
            'dashboard' => ['/dashboard', true],
            'nested path with query' => ['/admins/list?tab=audit', true],
            'query that mentions login' => ['/dashboard?next=/login', true],
            'path that merely starts with login' => ['/login-history', true],
            'login page' => ['/login', false],
            'login page with query' => ['/login?r=abc', false],
        ];
    }

    #[DataProvider('targets')]
    public function testAllows(string $target, bool $expected): void
    {
        self::assertSame($expected, (new AdminReturnTargetRestrictionPolicy())->allows($target));
    }
}
