<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-27 01:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use RuntimeException;

class InvalidOperationException extends RuntimeException
{
    public function __construct(
        string $entity,
        string $operation,
        ?string $reason = null
    )
    {
        parent::__construct(
            $reason !== null
                ? sprintf(
                'Invalid operation "%s" on %s: %s.',
                $operation,
                $entity,
                $reason
            )
                : sprintf(
                'Invalid operation "%s" on %s.',
                $operation,
                $entity
            )
        );
    }
}
