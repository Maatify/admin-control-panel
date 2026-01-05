<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\NotificationChannelType;
use App\Domain\Enum\VerificationFailureReasonEnum;
use App\Domain\Enum\VerificationPurposeEnum;
use Psr\Log\LoggerInterface;
use RuntimeException;

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
        $unifiedMessage = 'Verification failed. Please try again.';

        // 1. Validate OTP
        $result = $this->validator->validateByCode($otp);

        if (!$result->success) {
            $this->logger->warning('telegram_link_failed', [
                'reason' => VerificationFailureReasonEnum::INVALID_OTP->value,
                'chat_id' => $chatId,
            ]);
            return $unifiedMessage;
        }

        // 2. Check Purpose
        if ($result->purpose !== VerificationPurposeEnum::TelegramChannelLink) {
            $this->logger->warning('telegram_link_failed', [
                'reason' => VerificationFailureReasonEnum::OTP_WRONG_PURPOSE->value,
                'expected_purpose' => VerificationPurposeEnum::TelegramChannelLink->value,
                'actual_purpose' => $result->purpose->value,
                'chat_id' => $chatId,
            ]);
            return $unifiedMessage;
        }

        // 3. Check Identity Type
        if ($result->identityType !== IdentityTypeEnum::Admin) {
            $this->logger->warning('telegram_link_failed', [
                'reason' => VerificationFailureReasonEnum::IDENTITY_MISMATCH->value,
                'expected_identity_type' => IdentityTypeEnum::Admin->value,
                'actual_identity_type' => $result->identityType->value,
                'chat_id' => $chatId,
            ]);
            return $unifiedMessage;
        }

        $adminIdStr = $result->identityId;
        if (!is_numeric($adminIdStr)) {
            $this->logger->error('telegram_link_failed', [
                'reason' => VerificationFailureReasonEnum::INVALID_IDENTITY_ID->value,
                'identity_id' => $adminIdStr,
                'chat_id' => $chatId,
            ]);
            return $unifiedMessage;
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
            $this->logger->warning('telegram_link_failed', [
                'reason' => VerificationFailureReasonEnum::CHANNEL_ALREADY_LINKED->value, // Assuming existing channel throws generic RuntimeException or specific one
                'admin_id' => $adminId,
                'chat_id' => $chatId,
                'exception_message' => $e->getMessage(),
            ]);
            return $unifiedMessage;
        } catch (\Exception $e) {
             $this->logger->error('telegram_link_failed', [
                'reason' => VerificationFailureReasonEnum::CHANNEL_REGISTRATION_FAILED->value,
                'admin_id' => $adminId,
                'chat_id' => $chatId,
                'exception_message' => $e->getMessage(),
            ]);
            return $unifiedMessage;
        }

        return 'Telegram connected successfully!';
    }
}
