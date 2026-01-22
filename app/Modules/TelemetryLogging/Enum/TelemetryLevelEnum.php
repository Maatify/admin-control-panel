<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\Enum;

enum TelemetryLevelEnum: string
{
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
}
