<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 04:31
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Telemetry;
use App\Context\RequestContext;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;

/**
 * Request-scoped factory for HTTP telemetry recorders.
 *
 * WHY:
 * - HttpTelemetry*Recorder requires RequestContext (request-scoped)
 * - Container must not instantiate request-scoped objects
 * - Factory itself is container-friendly (depends only on TelemetryRecorderInterface)
 */
final readonly class HttpTelemetryRecorderFactory
{
    public function __construct(
        private TelemetryRecorderInterface $recorder
    ) {
    }

    public function admin(RequestContext $context): HttpTelemetryAdminRecorder
    {
        return new HttpTelemetryAdminRecorder(
            recorder: $this->recorder,
            context : $context
        );
    }

    public function system(RequestContext $context): HttpTelemetrySystemRecorder
    {
        return new HttpTelemetrySystemRecorder(
            recorder: $this->recorder,
            context : $context
        );
    }
}
