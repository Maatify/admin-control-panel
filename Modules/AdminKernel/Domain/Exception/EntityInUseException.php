<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-07 14:03
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\AdminKernel\Domain\Enum\AdminKernelErrorCodeEnum;
use Maatify\AdminKernel\Domain\Exception\Base\AdminKernelConflictExceptionBase;

class EntityInUseException extends AdminKernelConflictExceptionBase
{
    public function __construct(
        string $entity,
        string $code,
        string $usageContext
    )
    {
        parent::__construct(
            message: sprintf(
                '%s code "%s" cannot be changed because it is already used in %s.',
                $entity,
                $code,
                $usageContext
            ),
            errorCodeOverride: AdminKernelErrorCodeEnum::ENTITY_IN_USE
        );
    }
}
