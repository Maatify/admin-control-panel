<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Notification;

use Maatify\AdminKernel\Domain\Contracts\Admin\AdminNotificationChannelRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCode\VerificationCodeValidatorInterface;
use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\NotificationChannelType;
use Maatify\AdminKernel\Domain\Enum\VerificationFailureReasonEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * INTERNAL ADAPTER. NOT A DOMAIN SERVICE.
 * This class handles the boundary between Telegram Webhooks and Domain Logic.
 * It MUST NOT accumulate business logic.
 */
readonly class TelegramHandler
{
    public function __construct(
        private VerificationCodeValidatorInterface $validator,
        private AdminNotificationChannelRepositoryInterface $channelRepository,
        private LoggerInterface $logger
    ) {
    }

    public function handleStart(string $otp, string $chatId): string
    {
        // 1. Validate OTP
        $result = $this->validator->validateByCode($otp);

        if (!$result->success) {
            return $this->fail(VerificationFailureReasonEnum::INVALID_OTP, [
                'chat_id' => $chatId,
                'purpose' => $result->purpose?->value,
                'identity_type' => $result->identityType?->value,
                'identity_id' => $result->identityId,
            ]);
        }

        // 2. Check Purpose
        if ($result->purpose !== VerificationPurposeEnum::TelegramChannelLink) {
            return $this->fail(VerificationFailureReasonEnum::OTP_WRONG_PURPOSE, [
                'chat_id' => $chatId,
                'purpose' => $result->purpose?->value,
                'identity_type' => $result->identityType?->value,
                'identity_id' => $result->identityId,
            ]);
        }
        // Invariant: Purpose is verified non-null and correct
        assert($result->purpose !== null);

        // 3. Check Identity Type
        if ($result->identityType !== IdentityTypeEnum::Admin) {
            return $this->fail(VerificationFailureReasonEnum::IDENTITY_MISMATCH, [
                'chat_id' => $chatId,
                'purpose' => $result->purpose->value,
                'identity_type' => $result->identityType?->value,
                'identity_id' => $result->identityId,
            ]);
        }
        // Invariant: IdentityType is verified non-null and correct
        assert($result->identityType !== null);

        $adminIdStr = $result->identityId;
        if (!is_numeric($adminIdStr)) {
            return $this->fail(VerificationFailureReasonEnum::INVALID_IDENTITY_ID, [
                'chat_id' => $chatId,
                'purpose' => $result->purpose->value,
                'identity_type' => $result->identityType->value,
                'identity_id' => $result->identityId,
            ]);
        }
        $adminId = (int)$adminIdStr;

        // 4. Register Channel
        try {
            $this->channelRepository->registerChannel(
                $adminId,
                NotificationChannelType::TELEGRAM->value,
                ['chat_id' => $chatId]
            );
        } catch (RuntimeException $e) {
            return $this->fail(VerificationFailureReasonEnum::CHANNEL_ALREADY_LINKED, [
                'chat_id' => $chatId,
                'purpose' => $result->purpose->value,
                'identity_type' => $result->identityType->value,
                'identity_id' => $result->identityId,
                'exception_message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return $this->fail(VerificationFailureReasonEnum::CHANNEL_REGISTRATION_FAILED, [
                'chat_id' => $chatId,
                'purpose' => $result->purpose->value,
                'identity_type' => $result->identityType->value,
                'identity_id' => $result->identityId,
                'exception_message' => $e->getMessage(),
            ]);
        }

        return 'Telegram connected successfully!';
    }

    /**
     * @param array<string, mixed> $context
     */
    private function fail(
        VerificationFailureReasonEnum $reason,
        array $context = []
    ): string {
        $this->logger->warning('telegram_channel_link_failed', array_merge([
            'reason' => $reason->value,
        ], $context));

        return 'Unable to connect Telegram. Please try again.';
    }
}
