<?php

declare(strict_types=1);

namespace Maatify\Persistence\Exception;

/**
 * Marker interface for all package-defined exceptions in maatify/persistence.
 *
 * This allows consumers to catch package-defined exceptions using:
 *
 *     catch (PersistenceException $e) { ... }
 *
 * PDO or other external throwables may be passed through unchanged and are not
 * required to implement this contract.
 */
interface PersistenceException extends \Throwable
{
}
