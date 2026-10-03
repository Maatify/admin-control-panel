<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:23
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Contracts;

use Maatify\Validation\DTO\ValidationResultDTO;

interface ValidatorInterface
{
    /**
     * @param   array<string, mixed>  $input
     */
    public function validate(SchemaInterface $schema, array $input): ValidationResultDTO;
}
