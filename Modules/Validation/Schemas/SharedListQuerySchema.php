<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 02:03
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Contracts\SchemaInterface;
use Maatify\Validation\DTO\ValidationResultDTO;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\PaginationRule;
use Maatify\Validation\Rules\SearchQueryRule;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;

/**
 * Special schema for list query parameters.
 * Performs manual validation because it validates
 * root-level optional query parameters.
 */
final class SharedListQuerySchema implements SchemaInterface
{
    public function validate(array $input): ValidationResultDTO
    {
        $errors = [];
        $allowedKeys = ['page', 'per_page', 'search', 'date'];

        // 1. Root Level Validation: Reject forbidden keys
        $extraKeys = array_diff(array_keys($input), $allowedKeys);
        if (!empty($extraKeys)) {
            foreach ($extraKeys as $key) {
                $errors[$key] = [ValidationErrorCodeEnum::INVALID_VALUE];
            }
        }

        // 2. Validate 'page'
        if (array_key_exists('page', $input)) {
            try {
                PaginationRule::page()->assert($input['page']);
            } catch (ValidationException) {
                $errors['page'] = [ValidationErrorCodeEnum::INVALID_VALUE];
            }
        }

        // 3. Validate 'per_page'
        if (array_key_exists('per_page', $input)) {
            try {
                PaginationRule::perPage(100)->assert($input['per_page']);
            } catch (ValidationException) {
                $errors['per_page'] = [ValidationErrorCodeEnum::INVALID_VALUE];
            }
        }

        // 4. Validate 'search'
        if (array_key_exists('search', $input)) {
            // Requirement: Reject empty search blocks & Require global OR columns
            if (empty($input['search']) || !is_array($input['search'])) {
                $errors['search'] = [ValidationErrorCodeEnum::INVALID_VALUE];
            } else {
                $hasGlobal = isset($input['search']['global']);
                $hasColumns = isset($input['search']['columns']);

                if (! $hasGlobal && ! $hasColumns) {
                    $errors['search'] = [ValidationErrorCodeEnum::INVALID_VALUE];
                } else {
                    // Use existing rule for content validation
                    try {
                        SearchQueryRule::rule()->assert($input['search']);
                    } catch (ValidationException) {
                        $errors['search'] = [ValidationErrorCodeEnum::INVALID_VALUE];
                    }
                }
            }
        }

        // 5. Validate 'date'
        if (array_key_exists('date', $input)) {
            // Requirement: Atomic pair (from AND to)
            try {
                v::arrayType()->keySet(
                    v::key('from', v::date('Y-m-d'), true), // mandatory
                    v::key('to', v::date('Y-m-d'), true)    // mandatory
                )->assert($input['date']);
            } catch (ValidationException) {
                $errors['date'] = [ValidationErrorCodeEnum::INVALID_VALUE];
            }
        }

        return new ValidationResultDTO(
            valid: $errors === [],
            errors: $errors
        );
    }
}
