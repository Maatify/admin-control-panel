<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\Enum\IdentityType;
use App\Domain\Enum\NotificationChannelType;
use App\Domain\Enum\VerificationPurpose;

class TelegramHandler
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
        if ($result->purpose !== VerificationPurpose::TELEGRAM_CHANNEL_LINK) {
            return 'Invalid code purpose.';
        }

        if ($result->identityType !== IdentityType::ADMIN) {
            return 'Invalid identity type.';
        }

        $adminIdStr = $result->identityId;
        if (!is_numeric($adminIdStr)) {
            return 'Invalid admin ID.';
        }
        $adminId = (int)$adminIdStr;

        // 3. Register Channel
        $this->channelRepository->registerChannel(
            $adminId,
            NotificationChannelType::TELEGRAM->value, // Use value 'telegram'
            ['chat_id' => $chatId]
        );

        return 'Telegram connected successfully!';
    }
}
