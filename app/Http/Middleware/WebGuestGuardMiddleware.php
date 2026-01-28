<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;

/**
 * Minimal supporting class to allow declarative registration of Web Guest Guard.
 * Forces $isApi = false.
 */
class WebGuestGuardMiddleware extends GuestGuardMiddleware
{
    public function __construct(SessionValidationService $sessionValidationService)
    {
        parent::__construct($sessionValidationService, false);
    }
}
