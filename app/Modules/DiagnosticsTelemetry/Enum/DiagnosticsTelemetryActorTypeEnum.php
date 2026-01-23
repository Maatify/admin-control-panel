<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Enum;

enum DiagnosticsTelemetryActorTypeEnum: string implements DiagnosticsTelemetryActorTypeInterface
{
    case SYSTEM = 'SYSTEM';
    case ADMIN = 'ADMIN';
    case USER = 'USER';
    case SERVICE = 'SERVICE';
    case API_CLIENT = 'API_CLIENT';
    case ANONYMOUS = 'ANONYMOUS';

    public function value(): string
    {
        return $this->value;
    }
}
