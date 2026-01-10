<?php

declare(strict_types=1);

namespace App\Modules\Email\Queue;

use App\Modules\Email\DTO\RenderedEmailDTO;
use DateTimeInterface;

interface EmailQueueWriterInterface
{
    /**
     * Enqueues an email for sending.
     *
     * @param string $entityType The type of the entity associated with the email.
     * @param string|null $entityId The ID of the entity associated with the email.
     * @param string $recipientEmail The recipient's email address.
     * @param RenderedEmailDTO $email The rendered email content (subject, body, template key, language).
     * @param int $senderType The type of sender.
     * @param int $priority Priority level (default: 5).
     * @param DateTimeInterface|null $scheduledAt Scheduled time for sending (null for immediate).
     *
     * @return void
     */
    public function enqueue(
        string $entityType,
        ?string $entityId,
        string $recipientEmail,
        RenderedEmailDTO $email,
        int $senderType,
        int $priority = 5,
        ?DateTimeInterface $scheduledAt = null
    ): void;
}
