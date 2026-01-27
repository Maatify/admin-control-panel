<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-27 01:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

class EntityAlreadyExistsException extends RuntimeException
{
    public function __construct(
        string $entity,
        string $field,
        string $value
    ) {
        parent::__construct(
            sprintf('%s with %s "%s" already exists.', $entity, $field, $value)
        );
    }
}
