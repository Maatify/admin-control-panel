<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class ScopeNotAllowedException extends \RuntimeException
{
    public function __construct(string $scope)
    {
        parent::__construct("Invalid or inactive scope: {$scope}");
    }
}
