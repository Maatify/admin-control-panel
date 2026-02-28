<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\I18n\Domain\Enum\I18nErrorCodeEnum;

final class ScopeNotAllowedException extends I18nBusinessRuleException
{
    public function __construct(string $scope)
    {
        parent::__construct("Invalid or inactive scope: {$scope}");
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return I18nErrorCodeEnum::SCOPE_NOT_ALLOWED;
    }
}
