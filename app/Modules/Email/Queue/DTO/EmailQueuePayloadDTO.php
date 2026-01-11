<?php

declare(strict_types=1);

namespace App\Modules\Email\Queue\DTO;

/**
 * EmailQueuePayloadDTO
 *
 * Represents the exact payload to be stored in the email_queue.
 * This DTO carries the context and metadata required for queuing an email.
 */
final readonly class EmailQueuePayloadDTO
{
    /**
     * @param array<string, mixed> $context
     * @param string $templateKey
     * @param string $language
     */
    public function __construct(
        public array $context,
        public string $templateKey,
        public string $language
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'context' => $this->context,
            'templateKey' => $this->templateKey,
            'language' => $this->language,
        ];
    }
}
