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

        // 3. Check Identity Type
        if ($result->identityType !== IdentityTypeEnum::Admin) {
            return $this->fail(VerificationFailureReasonEnum::IDENTITY_MISMATCH, [
                'chat_id' => $chatId,
                'purpose' => $result->purpose?->value,
                'identity_type' => $result->identityType?->value,
                'identity_id' => $result->identityId,
            ]);
        }

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
