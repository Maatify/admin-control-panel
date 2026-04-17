<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\Application\Service;

use Maatify\ImageProfile\Application\Contract\ImageProfileRepositoryInterface;
use Maatify\ImageProfile\Application\Service\ToggleImageProfileService;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;
use Maatify\ImageProfile\Tests\Fixtures\ImageProfileFixtureFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\Application\Service\ToggleImageProfileService::class)]
final class ToggleImageProfileServiceTest extends TestCase
{
    private ImageProfileRepositoryInterface&MockObject $repository;
    private ToggleImageProfileService                  $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ImageProfileRepositoryInterface::class);
        $this->service    = new ToggleImageProfileService($this->repository);
    }

    // -------------------------------------------------------------------------
    // enable()
    // -------------------------------------------------------------------------

    public function test_enable_calls_toggle_active_with_true(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('toggleActive')
            ->with('my_profile', true)
            ->willReturn(ImageProfileFixtureFactory::standard());

        $this->service->enable('my_profile');
    }

    public function test_enable_returns_updated_entity(): void
    {
        $expected = ImageProfileFixtureFactory::standard();
        $this->repository->method('toggleActive')->willReturn($expected);

        $result = $this->service->enable('my_profile');

        self::assertSame($expected, $result);
    }

    public function test_enable_propagates_not_found(): void
    {
        $this->expectException(ImageProfileNotFoundException::class);

        $this->repository
            ->method('toggleActive')
            ->willThrowException(ImageProfileNotFoundException::forCode('ghost'));

        $this->service->enable('ghost');
    }

    // -------------------------------------------------------------------------
    // disable()
    // -------------------------------------------------------------------------

    public function test_disable_calls_toggle_active_with_false(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('toggleActive')
            ->with('my_profile', false)
            ->willReturn(ImageProfileFixtureFactory::inactive());

        $this->service->disable('my_profile');
    }

    public function test_disable_returns_updated_entity(): void
    {
        $expected = ImageProfileFixtureFactory::inactive();
        $this->repository->method('toggleActive')->willReturn($expected);

        $result = $this->service->disable('my_profile');

        self::assertSame($expected, $result);
    }

    public function test_disable_propagates_not_found(): void
    {
        $this->expectException(ImageProfileNotFoundException::class);

        $this->repository
            ->method('toggleActive')
            ->willThrowException(ImageProfileNotFoundException::forCode('ghost'));

        $this->service->disable('ghost');
    }

    // -------------------------------------------------------------------------
    // toggle() — explicit control
    // -------------------------------------------------------------------------

    public function test_toggle_passes_true_correctly(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('toggleActive')
            ->with('my_profile', true)
            ->willReturn(ImageProfileFixtureFactory::standard());

        $this->service->toggle('my_profile', true);
    }

    public function test_toggle_passes_false_correctly(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('toggleActive')
            ->with('my_profile', false)
            ->willReturn(ImageProfileFixtureFactory::inactive());

        $this->service->toggle('my_profile', false);
    }
}
