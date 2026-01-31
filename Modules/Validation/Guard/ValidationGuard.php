<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 10:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Guard;

use Maatify\Validation\Contracts\SchemaInterface;
use Maatify\Validation\Contracts\ValidatorInterface;
use Maatify\Validation\Exceptions\ValidationFailedException;

final readonly class ValidationGuard
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @param SchemaInterface $schema
     * @param array<string, mixed> $input
     * @return void
     * @throws ValidationFailedException
     */
    public function check(SchemaInterface $schema, array $input): void
    {
        $result = $this->validator->validate($schema, $input);

        if (!$result->isValid()) {
            throw new ValidationFailedException($result);
        }
    }
}
