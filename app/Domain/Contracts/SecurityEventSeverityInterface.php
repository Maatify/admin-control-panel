<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface SecurityEventSeverityInterface
{
    /**
     * Must return the canonical severity string.
     */
    public function toString(): string;
}
