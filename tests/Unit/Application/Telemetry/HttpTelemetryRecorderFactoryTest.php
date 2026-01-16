<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Telemetry;

use App\Application\Telemetry\HttpTelemetryAdminRecorder;
use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Application\Telemetry\HttpTelemetrySystemRecorder;
use App\Context\RequestContext;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;
use PHPUnit\Framework\TestCase;

final class HttpTelemetryRecorderFactoryTest extends TestCase
{
    public function testAdminReturnsAdminRecorder(): void
    {
        $recorder = $this->createMock(TelemetryRecorderInterface::class);
        $factory = new HttpTelemetryRecorderFactory($recorder);

        $context = new RequestContext('req-1', '127.0.0.1', 'test');

        $result = $factory->admin($context);

        $this->assertInstanceOf(HttpTelemetryAdminRecorder::class, $result);
    }

    public function testSystemReturnsSystemRecorder(): void
    {
        $recorder = $this->createMock(TelemetryRecorderInterface::class);
        $factory = new HttpTelemetryRecorderFactory($recorder);

        $context = new RequestContext('req-1', '127.0.0.1', 'test');

        $result = $factory->system($context);

        $this->assertInstanceOf(HttpTelemetrySystemRecorder::class, $result);
    }
}
