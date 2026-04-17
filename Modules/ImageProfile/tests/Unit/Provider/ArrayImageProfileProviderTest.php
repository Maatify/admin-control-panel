<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\Provider;

use Maatify\ImageProfile\Provider\ArrayImageProfileProvider;
use Maatify\ImageProfile\Tests\Fixtures\ImageProfileFixtureFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\Provider\ArrayImageProfileProvider::class)]
final class ArrayImageProfileProviderTest extends TestCase
{
    // -------------------------------------------------------------------------
    // findByCode()
    // -------------------------------------------------------------------------

    public function test_find_by_code_returns_matching_profile(): void
    {
        $profile  = ImageProfileFixtureFactory::standard();
        $provider = new ArrayImageProfileProvider($profile);

        $found = $provider->findByCode('standard_profile');

        self::assertSame($profile, $found);
    }

    public function test_find_by_code_returns_null_for_unknown_code(): void
    {
        $provider = new ArrayImageProfileProvider(ImageProfileFixtureFactory::standard());

        self::assertNull($provider->findByCode('does_not_exist'));
    }

    public function test_find_by_code_returns_inactive_profile(): void
    {
        // Provider must NOT filter by is_active — that is the validator's job
        $profile  = ImageProfileFixtureFactory::inactive();
        $provider = new ArrayImageProfileProvider($profile);

        $found = $provider->findByCode('inactive_profile');

        self::assertNotNull($found);
        self::assertFalse($found->isActive());
    }

    public function test_find_by_code_returns_null_on_empty_provider(): void
    {
        $provider = new ArrayImageProfileProvider();

        self::assertNull($provider->findByCode('anything'));
    }

    public function test_find_by_code_resolves_from_multiple_profiles(): void
    {
        $a = ImageProfileFixtureFactory::standard();
        $b = ImageProfileFixtureFactory::inactive();
        $c = ImageProfileFixtureFactory::webpOnly();

        $provider = new ArrayImageProfileProvider($a, $b, $c);

        self::assertSame($a, $provider->findByCode('standard_profile'));
        self::assertSame($b, $provider->findByCode('inactive_profile'));
        self::assertSame($c, $provider->findByCode('webp_only'));
    }

    // -------------------------------------------------------------------------
    // listAll()
    // -------------------------------------------------------------------------

    public function test_list_all_returns_all_profiles(): void
    {
        $a = ImageProfileFixtureFactory::standard();
        $b = ImageProfileFixtureFactory::inactive();

        $provider   = new ArrayImageProfileProvider($a, $b);
        $collection = $provider->listAll();

        self::assertSame(2, $collection->count());
    }

    public function test_list_all_returns_empty_collection_when_no_profiles(): void
    {
        $provider   = new ArrayImageProfileProvider();
        $collection = $provider->listAll();

        self::assertTrue($collection->isEmpty());
    }

    public function test_list_all_includes_inactive_profiles(): void
    {
        $provider   = new ArrayImageProfileProvider(
            ImageProfileFixtureFactory::standard(),
            ImageProfileFixtureFactory::inactive(),
        );
        $collection = $provider->listAll();

        self::assertSame(2, $collection->count());
    }

    // -------------------------------------------------------------------------
    // listActive()
    // -------------------------------------------------------------------------

    public function test_list_active_returns_only_active_profiles(): void
    {
        $provider = new ArrayImageProfileProvider(
            ImageProfileFixtureFactory::standard(),
            ImageProfileFixtureFactory::inactive(),
            ImageProfileFixtureFactory::unrestricted(),
        );

        $collection = $provider->listActive();

        self::assertSame(2, $collection->count());
        foreach ($collection as $profile) {
            self::assertTrue($profile->isActive());
        }
    }

    public function test_list_active_returns_empty_when_all_inactive(): void
    {
        $provider   = new ArrayImageProfileProvider(ImageProfileFixtureFactory::inactive());
        $collection = $provider->listActive();

        self::assertTrue($collection->isEmpty());
    }
}
