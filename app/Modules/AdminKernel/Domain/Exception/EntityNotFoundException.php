<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-27 01:52
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class EntityNotFoundException extends MaatifyException
{
    public function __construct(
        string $entity,
        string|int $identifier
    ) {
        parent::__construct(
            message: sprintf('%s "%s" was not found.', $entity, (string)$identifier),
            meta: [
                'entity'     => $entity,
                'identifier' => $identifier,
            ]
        );
    }

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::RESOURCE_NOT_FOUND;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::NOT_FOUND;
    }

    protected function defaultHttpStatus(): int
    {
        return 404;
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
