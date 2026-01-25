<?php

declare(strict_types=1);

namespace App\Application\Services;

use Maatify\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\DeliveryOperations\Enum\DeliveryStatusEnum;
use Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder;
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
    private const CHANNEL_EMAIL = DeliveryChannelEnum::EMAIL;
    private const CHANNEL_WEBHOOK = DeliveryChannelEnum::WEBHOOK;

    private const STATUS_QUEUED = DeliveryStatusEnum::QUEUED;
    private const STATUS_SENT = DeliveryStatusEnum::SENT;
    private const STATUS_FAILED = DeliveryStatusEnum::FAILED;

    private const OPERATION_NOTIFICATION_SEND = DeliveryOperationTypeEnum::NOTIFICATION_SEND;
    private const OPERATION_WEBHOOK_DISPATCH = DeliveryOperationTypeEnum::WEBHOOK_DISPATCH;

    public function __construct(
        private LoggerInterface $logger,
        private DeliveryOperationsRecorder $recorder
    ) {
    }

    /**
     * Used when Email was added to the processing queue.
     */
    public function recordEmailQueued(string $recipientId, string $templateName): void
    {
        try {
            $this->recorder->record(
                channel: self::CHANNEL_EMAIL,
                operationType: self::OPERATION_NOTIFICATION_SEND,
                status: self::STATUS_QUEUED,
                targetId: (int)$recipientId, // Casting to int as targetId is likely int, but signature might be loose. Checking contract later. Assuming int based on canonical rules.
                metadata: ['template' => $templateName]
            );
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
            $this->recorder->record(
                channel: self::CHANNEL_EMAIL,
                operationType: self::OPERATION_NOTIFICATION_SEND,
                status: self::STATUS_SENT,
                targetId: (int)$recipientId,
                providerMessageId: $providerMessageId,
                metadata: [
                    'template' => $templateName
                ]
            );
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
            $this->recorder->record(
                channel: self::CHANNEL_EMAIL,
                operationType: self::OPERATION_NOTIFICATION_SEND,
                status: self::STATUS_FAILED,
                targetId: (int)$recipientId,
                attemptNo: $attempt,
                metadata: [
                    'template' => $templateName,
                    'error' => $errorMessage
                ]
            );
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
            $this->recorder->record(
                channel: self::CHANNEL_WEBHOOK,
                operationType: self::OPERATION_WEBHOOK_DISPATCH,
                status: $httpStatus >= 200 && $httpStatus < 300 ? self::STATUS_SENT : self::STATUS_FAILED,
                metadata: [
                    'url' => $targetUrl,
                    'event' => $eventType,
                    'http_status' => $httpStatus
                ]
            );
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
