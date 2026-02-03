<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\DTO;

readonly class GenericEmailPayload implements EmailPayloadInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public array $context
    ) {
    }

    public function toArray(): array
    {
        return $this->context;
    }
}
