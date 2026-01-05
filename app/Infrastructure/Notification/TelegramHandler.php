<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\NotificationChannelType;
use App\Domain\Enum\VerificationPurposeEnum;

readonly class TelegramHandler
{
    public function __construct(
        private VerificationCodeValidatorInterface $validator,
        private AdminNotificationChannelRepositoryInterface $channelRepository
    ) {
    }

    public function handleStart(string $otp, string $chatId): string
    {
        // 1. Validate OTP
        $result = $this->validator->validateByCode($otp);

        if (!$result->success) {
            return 'Invalid or expired code.';
        }

        // 2. Check Purpose & Identity
        if ($result->purpose !== VerificationPurposeEnum::TelegramChannelLink) {
            return 'Invalid code purpose.';
        }

        if ($result->identityType !== IdentityTypeEnum::Admin) {
            return 'Invalid identity type.';
        }

        $adminIdStr = $result->identityId;
        if (!is_numeric($adminIdStr)) {
            return 'Invalid admin ID.';
        }
        $adminId = (int)$adminIdStr;

        // 3. Register Channel
        try {
            $this->channelRepository->registerChannel(
                $adminId,
                NotificationChannelType::TELEGRAM->value,
                ['chat_id' => $chatId]
            );
        } catch (\RuntimeException $e) {
            // Log error?
            return 'Failed to register channel. It might be in use.';
        }

        return 'Telegram connected successfully!';
    }
}
