<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\DTO;

use ImageProfileLegacy\tests\Fixtures\ImageProfileFixtureFactory;
use Maatify\ImageProfileLegacy\DTO\ImageProfileCollectionDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfileLegacy\DTO\ImageProfileCollectionDTO::class)]
final class ImageProfileCollectionDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function test_empty_factory_returns_empty_collection(): void
    {
        $collection = ImageProfileCollectionDTO::empty();

        self::assertTrue($collection->isEmpty());
        self::assertSame(0, $collection->count());
        self::assertNull($collection->first());
    }

    public function test_constructs_with_profiles(): void
    {
        $a = ImageProfileFixtureFactory::standard();
        $b = ImageProfileFixtureFactory::inactive();

        $collection = new ImageProfileCollectionDTO($a, $b);

        self::assertSame(2, $collection->count());
        self::assertFalse($collection->isEmpty());
    }

    // -------------------------------------------------------------------------
    // first()
    // -------------------------------------------------------------------------

    public function test_first_returns_first_profile(): void
    {
        $a = ImageProfileFixtureFactory::standard();
        $b = ImageProfileFixtureFactory::inactive();

        $collection = new ImageProfileCollectionDTO($a, $b);

        self::assertSame($a, $collection->first());
    }

    // -------------------------------------------------------------------------
    // with() — immutability
    // -------------------------------------------------------------------------

    public function test_with_returns_new_instance(): void
    {
        $original = ImageProfileCollectionDTO::empty();
        $extended = $original->with(ImageProfileFixtureFactory::standard());

        self::assertTrue($original->isEmpty());
        self::assertSame(1, $extended->count());
    }

    // -------------------------------------------------------------------------
    // filterActive()
    // -------------------------------------------------------------------------

    public function test_filter_active_returns_only_active(): void
    {
        $active   = ImageProfileFixtureFactory::standard();
        $inactive = ImageProfileFixtureFactory::inactive();

        $collection = new ImageProfileCollectionDTO($active, $inactive);
        $filtered   = $collection->filterActive();

        self::assertSame(1, $filtered->count());
        self::assertTrue($filtered->first()?->isActive());
    }

    public function test_filter_active_on_all_active_returns_same_count(): void
    {
        $a = ImageProfileFixtureFactory::standard();
        $b = ImageProfileFixtureFactory::unrestricted();

        $collection = new ImageProfileCollectionDTO($a, $b);
        $filtered   = $collection->filterActive();

        self::assertSame(2, $filtered->count());
    }

    public function test_filter_active_on_all_inactive_returns_empty(): void
    {
        $collection = new ImageProfileCollectionDTO(ImageProfileFixtureFactory::inactive());
        $filtered   = $collection->filterActive();

        self::assertTrue($filtered->isEmpty());
    }

    public function test_filter_active_is_non_mutating(): void
    {
        $active   = ImageProfileFixtureFactory::standard();
        $inactive = ImageProfileFixtureFactory::inactive();

        $collection = new ImageProfileCollectionDTO($active, $inactive);
        $collection->filterActive();

        // Original is unchanged
        self::assertSame(2, $collection->count());
    }

    // -------------------------------------------------------------------------
    // Iteration
    // -------------------------------------------------------------------------

    public function test_is_iterable(): void
    {
        $a = ImageProfileFixtureFactory::standard();
        $b = ImageProfileFixtureFactory::unrestricted();

        $collection = new ImageProfileCollectionDTO($a, $b);

        $seen = [];
        foreach ($collection as $profile) {
            $seen[] = $profile->code;
        }

        self::assertSame(['standard_profile', 'unrestricted'], $seen);
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    public function test_json_serialize_returns_list_of_profiles(): void
    {
        $a = ImageProfileFixtureFactory::standard();

        $collection = new ImageProfileCollectionDTO($a);
        $data       = $collection->jsonSerialize();

        self::assertCount(1, $data);
        self::assertSame($a, $data[0]);
    }

    public function test_json_encode_produces_array(): void
    {
        $collection = ImageProfileCollectionDTO::empty();
        $json       = json_encode($collection);

        self::assertSame('[]', $json);
    }
}
