<?php

declare(strict_types=1);

namespace App\Infrastructure\Context;

interface CorrelationIdProviderInterface
{
    /**
     * Retrieves the current correlation ID.
     * MUST throw if context is unavailable (Fail-Closed requirement for Authoritative Audit).
     *
     * @throws \RuntimeException
     */
    public function getCorrelationId(): string;
}
