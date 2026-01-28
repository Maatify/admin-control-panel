<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;

/**
 * Minimal supporting class to allow declarative registration of API Guest Guard.
 * Forces $isApi = true.
 */
class ApiGuestGuardMiddleware extends GuestGuardMiddleware
{
    public function __construct(SessionValidationService $sessionValidationService)
    {
        parent::__construct($sessionValidationService, true);
    }
}
