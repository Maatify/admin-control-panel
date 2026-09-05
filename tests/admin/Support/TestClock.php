<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-30 20:52
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Support;

use Maatify\SharedCommon\Contracts\ClockInterface;
use DateTimeImmutable;
use DateTimeZone;

final class TestClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
        private DateTimeZone $tz
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->tz;
    }
}
