<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Enum;

enum SecuritySignalSeverityEnum: string
{
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
}
