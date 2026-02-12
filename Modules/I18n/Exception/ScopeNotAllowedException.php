<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\SharedCommon\Exception\MaatifyException;

final class ScopeNotAllowedException extends MaatifyException
{
    public function __construct(string $scope)
    {
        parent::__construct("Invalid or inactive scope: {$scope}");
    }
}
