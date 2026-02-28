<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\I18n\Domain\Enum\I18nErrorCodeEnum;

final class DomainNotAllowedException extends I18nBusinessRuleException
{
    public function __construct(string $domain)
    {
        parent::__construct("Invalid or inactive domain: {$domain}");
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return I18nErrorCodeEnum::DOMAIN_NOT_ALLOWED;
    }
}

