<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Unit\Domain\ActivityLog\Recorder;

use App\Domain\ActivityLog\Recorder\ActivityRecorder;
use App\Modules\ActivityLog\Enums\CoreActivityAction;
use Tests\Fakes\FakeActivityLogWriter;
use PHPUnit\Framework\TestCase;

final class ActivityRecorderTest extends TestCase
{
    public function test_it_logs_activity_using_enum(): void
    {
        $writer = new FakeActivityLogWriter();
        $service = new ActivityRecorder($writer);

        $service->log(
            action    : CoreActivityAction::ADMIN_USER_UPDATE,
            actorType : 'admin',
            actorId   : 1,
            entityType: 'user',
            entityId  : 42,
        );

        $this->assertNotNull($writer->lastActivity);
        $this->assertSame('admin.user.update', $writer->lastActivity->action);
        $this->assertSame('admin', $writer->lastActivity->actorType);
        $this->assertSame(42, $writer->lastActivity->entityId);
    }

    public function test_it_does_not_throw_if_writer_fails(): void
    {
        $writer = new FakeActivityLogWriter();
        $writer->throwException = true;

        $service = new ActivityRecorder($writer);

        $this->expectNotToPerformAssertions();

        $service->log(
            action: 'test.action',
            actorType: 'system',
            actorId: null,
        );
    }

}
