<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\DTO;

readonly class RenderedEmailDTO
{
    public function __construct(
        public string $subject,
        public string $htmlBody,
        public string $templateKey,
        public string $language
    ) {
    }
}
