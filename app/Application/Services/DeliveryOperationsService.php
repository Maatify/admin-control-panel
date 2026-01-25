<?php

declare(strict_types=1);

namespace App\Application\Services;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Tracks the lifecycle of asynchronous delivery operations (Email, SMS, Webhooks, Jobs).
 *
 * BEHAVIOR GUARANTEE: FAIL-OPEN (Best Effort)
 * Logging status updates MUST NOT disrupt the actual delivery process.
 */
class DeliveryOperationsService
{
    private const CHANNEL_EMAIL = 'email';
    private const CHANNEL_WEBHOOK = 'webhook';

    private const STATUS_QUEUED = 'queued';
    private const STATUS_SENT = 'sent';
    private const STATUS_FAILED = 'failed';

    private const OPERATION_NOTIFICATION_SEND = 'notification.send';
    private const OPERATION_WEBHOOK_DISPATCH = 'webhook.dispatch';

    public function __construct(
        private LoggerInterface $logger,
        // private DeliveryOperationsRecorder $recorder // Dependency to be injected
    ) {
    }

    /**
     * Used when Email was added to the processing queue.
     */
    public function recordEmailQueued(string $recipientId, string $templateName): void
    {
        try {
            // $this->recorder->record(
            //     channel: self::CHANNEL_EMAIL,
            //     operationType: self::OPERATION_NOTIFICATION_SEND,
            //     status: self::STATUS_QUEUED,
            //     targetId: $recipientId,
            //     metadata: ['template' => $templateName]
            // );
        } catch (Throwable $e) {
            $this->logFailure('recordEmailQueued', $e);
        }
    }

    /**
     * Used when Provider accepted the message.
     */
    public function recordEmailSent(string $recipientId, string $templateName, string $providerMessageId): void
    {
        try {
            // $this->recorder->record(
            //     channel: self::CHANNEL_EMAIL,
            //     operationType: self::OPERATION_NOTIFICATION_SEND,
            //     status: self::STATUS_SENT,
            //     targetId: $recipientId,
            //     metadata: [
            //         'template' => $templateName,
            //         'provider_msg_id' => $providerMessageId
            //     ]
            // );
        } catch (Throwable $e) {
            $this->logFailure('recordEmailSent', $e);
        }
    }

    /**
     * Used when Delivery failed.
     */
    public function recordEmailFailed(string $recipientId, string $templateName, string $errorMessage, int $attempt): void
    {
        try {
            // $this->recorder->record(
            //     channel: self::CHANNEL_EMAIL,
            //     operationType: self::OPERATION_NOTIFICATION_SEND,
            //     status: self::STATUS_FAILED,
            //     targetId: $recipientId,
            //     metadata: [
            //         'template' => $templateName,
            //         'error' => $errorMessage,
            //         'attempt' => $attempt
            //     ]
            // );
        } catch (Throwable $e) {
            $this->logFailure('recordEmailFailed', $e);
        }
    }

    /**
     * Used when a webhook payload was sent to an external subscriber.
     */
    public function recordWebhookDispatched(string $targetUrl, string $eventType, int $httpStatus): void
    {
        try {
            // $this->recorder->record(
            //     channel: self::CHANNEL_WEBHOOK,
            //     operationType: self::OPERATION_WEBHOOK_DISPATCH,
            //     status: $httpStatus >= 200 && $httpStatus < 300 ? self::STATUS_SENT : self::STATUS_FAILED,
            //     targetId: null,
            //     metadata: [
            //         'url' => $targetUrl,
            //         'event' => $eventType,
            //         'http_status' => $httpStatus
            //     ]
            // );
        } catch (Throwable $e) {
            $this->logFailure('recordWebhookDispatched', $e);
        }
    }

    private function logFailure(string $method, Throwable $e): void
    {
        $this->logger->error(
            sprintf('[DeliveryOperationsService] %s failed: %s', $method, $e->getMessage()),
            ['exception' => $e]
        );
    }
}
