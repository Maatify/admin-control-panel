<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface SecurityEventTypeInterface
{
    /**
     * Must return the canonical event type string.
     */
    public function toString(): string;
}
