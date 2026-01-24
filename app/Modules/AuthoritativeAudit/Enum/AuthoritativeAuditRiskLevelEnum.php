<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Enum;

enum AuthoritativeAuditRiskLevelEnum: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';
}
