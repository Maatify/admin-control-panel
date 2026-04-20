<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class WebsiteUiThemeCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'entity_type' => [v::stringType()->notEmpty()->length(1, 50), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'theme_file' => [v::stringType()->notEmpty()->length(1, 255), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'display_name' => [v::stringType()->notEmpty()->length(1, 150), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
