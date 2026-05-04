<?php

declare(strict_types=1);

namespace Maatify\Persistence\Exception;

/**
 * Marker interface for all exceptions thrown by maatify/persistence.
 *
 * This allows consumers to catch all package-level exceptions using:
 *
 *     catch (PersistenceException $e) { ... }
 *
 * while individual exceptions may still extend SPL exception types such as
 * InvalidArgumentException or RuntimeException for compatibility.
 */
interface PersistenceException
{
}
