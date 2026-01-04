<?php

declare(strict_types=1);

namespace App\Domain\Audit;

/**
 * Developer Hint: Standard target types.
 *
 * This class serves as a reference for consistent naming of target types.
 * It is NOT enforced by the database or domain logic to allow extensibility.
 */
final class AuditTargetTypes
{
    public const ADMIN = 'admin';
    public const ADMIN_EMAIL = 'admin_email';
    public const ROLE = 'role';
    public const PERMISSION = 'permission';
    public const PRODUCT = 'product';
    public const SELF = 'self';
    public const SYSTEM = 'system';
}
