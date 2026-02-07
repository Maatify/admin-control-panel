<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class DomainScopeViolationException extends \RuntimeException
{
    public function __construct(string $scope, string $domain)
    {
        parent::__construct(
            "Domain '{$domain}' is not allowed for scope '{$scope}'."
        );
    }
}
