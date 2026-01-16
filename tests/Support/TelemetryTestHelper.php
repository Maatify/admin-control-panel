<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Telemetry\DTO\TelemetryRecordDTO;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;
use App\Modules\Validation\DTO\ValidationResultDTO;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

final class SpyRecorder implements TelemetryRecorderInterface
{
    /** @var TelemetryRecordDTO[] */
    public array $records = [];

    public function record(TelemetryRecordDTO $dto): void
    {
        $this->records[] = $dto;
    }
}

final class TelemetryTestHelper
{
    /**
     * Creates a final HttpTelemetryRecorderFactory instance with a SpyRecorder injected.
     *
     * @return array{factory: HttpTelemetryRecorderFactory, recorder: SpyRecorder}
     */
    public static function makeFactoryWithSpyRecorder(?object $otherDependency = null): array
    {
        $ref = new ReflectionClass(HttpTelemetryRecorderFactory::class);
        /** @var HttpTelemetryRecorderFactory $factory */
        $factory = $ref->newInstanceWithoutConstructor();

        $recorder = new SpyRecorder();

        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();

            if (str_contains($typeName, 'TelemetryRecorder') || str_contains($typeName, 'RecorderInterface')) {
                 $prop->setAccessible(true);
                 $prop->setValue($factory, $recorder);
            } elseif ($otherDependency !== null && is_a($otherDependency, $typeName)) {
                 $prop->setAccessible(true);
                 $prop->setValue($factory, $otherDependency);
            }
        }

        return ['factory' => $factory, 'recorder' => $recorder];
    }

    public static function makeValidValidationResultDTO(array $validatedData = []): ValidationResultDTO
    {
        $ref = new ReflectionClass(ValidationResultDTO::class);
        $dto = $ref->newInstanceWithoutConstructor();

        // Best-effort hydration
        $obj = new \ReflectionObject($dto);

        // valid = true
        if ($obj->hasProperty('valid')) {
            $p = $obj->getProperty('valid');
            $p->setAccessible(true);
            $p->setValue($dto, true);
        }

        // data/validatedData/input if exists
        foreach (['validatedData', 'validated', 'data', 'payload', 'input'] as $propName) {
            if ($obj->hasProperty($propName)) {
                $p = $obj->getProperty($propName);
                $p->setAccessible(true);
                try { $p->setValue($dto, $validatedData); } catch (Throwable) {}
            }
        }

        return $dto;
    }

    public static function makeTotpVerificationResultDTO(bool $success, ?string $reason = null): TotpVerificationResultDTO
    {
        $ref = new ReflectionClass(TotpVerificationResultDTO::class);
        $dto = $ref->newInstanceWithoutConstructor();

        $obj = new \ReflectionObject($dto);

        if ($obj->hasProperty('success')) {
            $p = $obj->getProperty('success');
            $p->setAccessible(true);
            $p->setValue($dto, $success);
        }

        if ($obj->hasProperty('errorReason')) {
             $p = $obj->getProperty('errorReason');
             $p->setAccessible(true);
             $p->setValue($dto, $reason);
        }

        return $dto;
    }
}
