<?php

declare(strict_types=1);

namespace Maatify\SharedCommon\Bootstrap;

use DateTimeZone;
use DI\ContainerBuilder;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\SharedCommon\Infrastructure\SystemClock;
use Psr\Container\ContainerInterface;

class SharedCommonBindings
{
    /**
     * Registers default bindings for SharedCommon contracts.
     *
     * @param ContainerBuilder<\DI\Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            ClockInterface::class => function (ContainerInterface $c) {
                // Determine timezone (fallback to UTC if not configured)
                $tzString = date_default_timezone_get() ?: 'UTC';
                $timezone = new DateTimeZone($tzString);

                return new SystemClock($timezone);
            },
        ]);
    }
}
