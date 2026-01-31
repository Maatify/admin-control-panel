<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Contracts\SchemaInterface;
use Maatify\Validation\DTO\ValidationResultDTO;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Exceptions\ValidationException;

abstract class AbstractSchema implements SchemaInterface
{
    /**
     * لازم كل Schema ترجع:
     * [
     *   'field_name' => [Validatable, ValidationErrorCodeEnum],
     * ]
     *
     * @return array<string, array{0: \Respect\Validation\Validatable, 1: \Maatify\Validation\Enum\ValidationErrorCodeEnum}>
     */
    abstract protected function rules(): array;

    /**
     * @param   array<string, mixed>  $input
     */
    final public function validate(array $input): ValidationResultDTO
    {
        $errors = [];

        foreach ($this->rules() as $field => [$rule, $errorCode]) {
            try {
                $rule->assert($input[$field] ?? null);
//            } catch (NestedValidationException) {
//            } catch (ValidationException) {  // Catch parent exception for broader coverage without changing behavior
//            }
            } catch (NestedValidationException | ValidationException) {
                $errors[$field] = [$errorCode];
            }
        }

        return new ValidationResultDTO(
            valid : $errors === [],
            errors: $errors
        );
    }
}
