<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Auth;

use Maatify\Validation\Contracts\SchemaInterface;
use Maatify\Validation\DTO\ValidationResultDTO;
use Maatify\Validation\Rules\RequiredStringRule;
use Respect\Validation\Validator as v;

readonly class RedirectTokenSignSchema implements SchemaInterface
{
    public function validate(array $input): ValidationResultDTO
    {
        $pathRule = new RequiredStringRule();

        $pathResult = $pathRule->validate('path', $input);

        if (!$pathResult) {
            return new ValidationResultDTO(false, ['path' => 'Path is required']);
        }

        return new ValidationResultDTO(true);
    }
}
