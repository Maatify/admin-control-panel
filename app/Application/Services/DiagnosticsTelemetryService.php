<?php

declare(strict_types=1);

namespace App\Application\Services;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Captures technical health metrics, performance data, and system errors.
 *
 * BEHAVIOR GUARANTEE: FAIL-OPEN (Best Effort)
 * Telemetry failures MUST be invisible to the application flow.
 */
class DiagnosticsTelemetryService
{
    private const EVENT_EXCEPTION_SYSTEM = 'exception.system';
    private const EVENT_PERF_METRIC = 'perf.metric';
    private const EVENT_DEPENDENCY_FAILURE = 'dependency.failure';

    private const SEVERITY_ERROR = 'ERROR';
    private const SEVERITY_INFO = 'INFO';
    private const SEVERITY_WARNING = 'WARNING';

    public function __construct(
        private LoggerInterface $logger,
        // private DiagnosticsTelemetryRecorder $recorder // Dependency to be injected
    ) {
    }

    /**
     * Used when an unhandled or critical exception occurred.
     */
    public function recordSystemException(string $message, string $file, int $line, string $exceptionClass): void
    {
        try {
            // $this->recorder->record(
            //     eventKey: self::EVENT_EXCEPTION_SYSTEM,
            //     severity: self::SEVERITY_ERROR,
            //     actorId: null, // System event
            //     metadata: [
            //         'message' => $message,
            //         'file' => $file,
            //         'line' => $line,
            //         'class' => $exceptionClass
            //     ]
            // );
        } catch (Throwable $e) {
            $this->logFailure('recordSystemException', $e);
        }
    }

    /**
     * Used when measuring execution time of a specific operation.
     */
    public function recordPerformanceMetric(string $metricName, int $durationMs, string $context): void
    {
        try {
            // $this->recorder->record(
            //     eventKey: self::EVENT_PERF_METRIC,
            //     severity: self::SEVERITY_INFO,
            //     actorId: null, // System event
            //     metadata: [
            //         'metric' => $metricName,
            //         'duration_ms' => $durationMs,
            //         'context' => $context
            //     ]
            // );
        } catch (Throwable $e) {
            // Intentionally ignored for performance metrics to avoid noise
        }
    }

    /**
     * Used when a 3rd party service returned an error.
     */
    public function recordExternalDependencyFailure(string $serviceName, string $endpoint, int $statusCode): void
    {
        try {
            // $this->recorder->record(
            //     eventKey: self::EVENT_DEPENDENCY_FAILURE,
            //     severity: self::SEVERITY_WARNING,
            //     actorId: null, // System event
            //     metadata: [
            //         'service' => $serviceName,
            //         'endpoint' => $endpoint,
            //         'status_code' => $statusCode
            //     ]
            // );
        } catch (Throwable $e) {
            // Intentionally ignored to avoid cascading failures
        }
    }

    private function logFailure(string $method, Throwable $e): void
    {
        // Fallback to primitive error log if LoggerInterface is unavailable or failing
        error_log(sprintf('[DiagnosticsTelemetryService] %s failed: %s', $method, $e->getMessage()));
    }
}
