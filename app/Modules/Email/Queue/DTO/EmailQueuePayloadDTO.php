<?php

declare(strict_types=1);

namespace App\Modules\Email\Queue\DTO;

/**
 * EmailQueuePayloadDTO
 *
 * Represents the exact payload to be stored in the email_queue.
 * This DTO carries the content and metadata required for queuing an email.
 */
final readonly class EmailQueuePayloadDTO
{
    public function __construct(
        public string $subject,
        public string $htmlBody,
        public string $templateKey,
        public string $language
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'htmlBody' => $this->htmlBody,
            'templateKey' => $this->templateKey,
            'language' => $this->language,
        ];
    }
}
