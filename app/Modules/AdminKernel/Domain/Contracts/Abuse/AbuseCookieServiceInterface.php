<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Abuse;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\DTO\Abuse\AbuseCookieIssueDTO;

interface AbuseCookieServiceInterface
{
    public function issue(string $sessionToken, RequestContext $context, ?string $existingDeviceId): AbuseCookieIssueDTO;
}
