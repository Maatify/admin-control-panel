<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-20 10:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Verification;

use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;

interface VerificationNotificationDispatcherInterface
{
    /**
     * Dispatch verification notification to the appropriate channel
     * (Email / Telegram / future).
     *
     * @param   array<string, mixed>  $context
     */
    public function dispatch(
        IdentityTypeEnum $identityType,
        string $identityId,
        VerificationPurposeEnum $purpose,
        string $recipient,
        string $plainCode,
        array $context,
        string $language
    ): void;
}
