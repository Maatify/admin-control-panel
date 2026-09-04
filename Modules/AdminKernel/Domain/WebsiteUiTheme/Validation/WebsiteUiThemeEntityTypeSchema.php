<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\WebsiteUiTheme\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Semantic\WebsiteUiThemeIdentifierRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class WebsiteUiThemeEntityTypeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'entity_type' => [WebsiteUiThemeIdentifierRule::entityType(), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
