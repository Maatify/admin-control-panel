<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\DTO;

/**
 * Marker interface for all email payload DTOs.
 *
 * Ensures a unified contract for rendering and queue layers.
 */
interface EmailPayloadInterface
{
    /**
     * Return payload as a render-ready array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
