<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Telemetry;

use App\Application\Telemetry\HttpTelemetryAdminRecorder;
use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Application\Telemetry\HttpTelemetrySystemRecorder;
use App\Context\RequestContext;
use App\Domain\Telemetry\DTO\TelemetryRecordDTO;
use App\Domain\Telemetry\Enum\TelemetryActorTypeEnum;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\TelemetryTestHelper;

final class HttpTelemetryRecorderFactoryTest extends TestCase
{
    public function testAdminReturnsAdminRecorder(): void
    {
        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        /** @var HttpTelemetryRecorderFactory $factory */
        $factory = $helper['factory'];

        $context = new RequestContext('req-1', '127.0.0.1', 'test');

        $result = $factory->admin($context);

        $this->assertInstanceOf(HttpTelemetryAdminRecorder::class, $result);

        // Ensure it uses admin actor type
        // HttpTelemetryAdminRecorder doesn't expose public methods to check actor type easily without recording
        // But we can verify it records with ADMIN actor type
        $result->record(123, \App\Modules\Telemetry\Enum\TelemetryEventTypeEnum::RESOURCE_MUTATION, \App\Modules\Telemetry\Enum\TelemetrySeverityEnum::INFO);

        $spy = $helper['recorder'];
        $this->assertCount(1, $spy->records);
        $this->assertEquals(TelemetryActorTypeEnum::ADMIN, $spy->records[0]->actorType);
    }

    public function testSystemReturnsSystemRecorder(): void
    {
        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        /** @var HttpTelemetryRecorderFactory $factory */
        $factory = $helper['factory'];

        $context = new RequestContext('req-1', '127.0.0.1', 'test');

        $result = $factory->system($context);

        $this->assertInstanceOf(HttpTelemetrySystemRecorder::class, $result);

        // Ensure it uses system actor type
        $result->record(\App\Modules\Telemetry\Enum\TelemetryEventTypeEnum::RESOURCE_MUTATION, \App\Modules\Telemetry\Enum\TelemetrySeverityEnum::INFO);

        $spy = $helper['recorder'];
        $this->assertCount(1, $spy->records);
        $this->assertEquals(TelemetryActorTypeEnum::SYSTEM, $spy->records[0]->actorType);
    }
}
