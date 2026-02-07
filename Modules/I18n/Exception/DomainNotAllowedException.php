<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

final class DomainNotAllowedException extends \RuntimeException
{
    public function __construct(string $domain)
    {
        parent::__construct("Invalid or inactive domain: {$domain}");
    }
}
