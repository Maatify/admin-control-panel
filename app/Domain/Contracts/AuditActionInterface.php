<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AuditActionInterface
{
    /**
     * Must return the canonical action string.
     */
    public function toString(): string;
}
