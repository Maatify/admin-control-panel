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

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class EntityInUseException extends MaatifyException
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
            meta: [
                'entity'       => $entity,
                'code'         => $code,
                'usageContext' => $usageContext,
            ]
        );
    }

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::ENTITY_IN_USE;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::CONFLICT;
    }

    protected function defaultHttpStatus(): int
    {
        return 409;
    }

    protected function defaultIsSafe(): bool
    {
        return true;
    }

    protected function defaultIsRetryable(): bool
    {
        return false;
    }
}
