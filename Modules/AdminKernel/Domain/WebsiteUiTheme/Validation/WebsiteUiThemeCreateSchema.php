<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Rules\Semantic\WebsiteUiThemeIdentifierRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class WebsiteUiThemeCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'entity_type' => [WebsiteUiThemeIdentifierRule::entityType(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'theme_file' => [WebsiteUiThemeIdentifierRule::themeFile(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'display_name' => [StringRule::required(min: 1, max: 150), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
