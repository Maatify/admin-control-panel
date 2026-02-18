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

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class InvalidOperationException extends MaatifyException
{
    public function __construct(
        string $entity,
        string $operation,
        ?string $reason = null
    )
    {
        parent::__construct(
            message: $reason !== null
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
                ),
            meta: [
                'entity'    => $entity,
                'operation' => $operation,
                'reason'    => $reason,
            ]
        );
    }

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::INVALID_OPERATION;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::BUSINESS_RULE;
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
