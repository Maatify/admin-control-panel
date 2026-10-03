<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:47
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Enum;

enum AuthErrorCodeEnum: string
{
    case AUTH_REQUIRED = 'auth_required';
    case STEP_UP_REQUIRED = 'step_up_required';
    case NOT_AUTHORIZED = 'not_authorized';
}
